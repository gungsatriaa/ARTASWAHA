<?php

namespace App\Services;

use App\Jobs\GenerateContentAudio;
use App\Models\ContentAudio;
use App\Models\Lesson;
use App\Models\QuizQuestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ContentAudioService
{
    public const MAX_TEXT_LENGTH = 16000;

    public const MAX_AUDIO_BYTES = 67108864;

    public const GENERATION_TIMEOUT = 1800;

    public function isConfigured(): bool
    {
        if (! config('services.tts.enabled')) {
            return false;
        }
        if (config('services.tts.engine') === 'chatterbox') {
            $reference = (string) config('services.tts.reference_audio');
            if ($reference !== '' && (! is_file($reference) || ! is_readable($reference))) {
                return false;
            }
            $directory = rtrim((string) config('services.tts.chatterbox_path'), '/\\');
            foreach (['manifest.json', 'base/ve.pt', 'base/s3gen.pt', 'base/tokenizer.json', 'base/conds.pt', 'indonesian/t3_cfg.safetensors'] as $file) {
                if (! is_file($directory.'/'.$file) || ! is_readable($directory.'/'.$file) || filesize($directory.'/'.$file) === 0) {
                    return false;
                }
            }

            return is_file((string) config('services.tts.python'))
                && in_array(config('services.tts.device'), ['cpu', 'cuda'], true);
        }
        if (config('services.tts.engine') === 'openai') {
            $url = (string) config('services.tts.url');

            return filter_var($url, FILTER_VALIDATE_URL) !== false
                && in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
                && filled(config('services.tts.model')) && filled(config('services.tts.voice'));
        }
        if (config('services.tts.engine') === 'mms') {
            $directory = rtrim((string) config('services.tts.model_path'), '/\\');

            return is_file((string) config('services.tts.python')) && is_file($directory.'/config.json')
                && is_file($directory.'/model.safetensors') && is_file($directory.'/vocab.json');
        }

        return config('services.tts.engine', 'espeak') === 'espeak' && is_file((string) config('services.tts.binary'));
    }

    public function sourceHash(Lesson|QuizQuestion $content, int $slot = 0): string
    {
        if ($content instanceof Lesson) {
            return hash('sha256', json_encode(['lesson-audio-v2', $slot, $this->defaultText($content, $slot)], JSON_THROW_ON_ERROR));
        }

        return hash('sha256', json_encode([$content->prompt, $content->options], JSON_THROW_ON_ERROR));
    }

    public function defaultText(Lesson|QuizQuestion $content, int $slot = 0): string
    {
        if ($content instanceof Lesson && $slot > 0) {
            $question = $content->exerciseQuestions()[$slot - 1] ?? null;
            abort_if($question === null, 404);
            $text = $question['exercise_prompt'];
            $options = $question['exercise_options'] ?? [];
        } else {
            $text = $content instanceof Lesson ? $content->title.".\n".$content->content : $content->prompt;
            $options = $content instanceof QuizQuestion ? $content->options : [];
        }
        foreach ($options ?? [] as $index => $option) {
            $text .= "\n".chr(65 + $index).'. '.$option;
        }

        return mb_substr($content instanceof Lesson ? $this->cleanNarration($text) : $text, 0, self::MAX_TEXT_LENGTH);
    }

    private function cleanNarration(string $text): string
    {
        $text = preg_replace('/^#{1,6}\h+Sumber belajar\h*\R.*?(?=^#{1,6}\h|\z)/imsu', '', $text) ?? $text;
        $text = preg_replace('/!\[[^\]]*\]\([^)]*\)/u', '', $text) ?? $text;
        $text = preg_replace('/\[([^\]]+)\]\([^)]*\)/u', '$1', $text) ?? $text;
        $text = preg_replace('~https?://[^\s<>]+~iu', '', $text) ?? $text;
        $text = preg_replace('/^\h*(?:#{1,6}\h+|[-*+]\h+|>\h*)/mu', '', $text) ?? $text;
        $text = str_replace(['**', '__', '`'], '', strip_tags($text));

        return trim(preg_replace('/\n{3,}/', "\n\n", $text) ?? $text);
    }

    public function forContent(Lesson|QuizQuestion $content, int $slot = 0): ?ContentAudio
    {
        if ($content instanceof Lesson && $slot > 0) {
            return ContentAudio::where('lesson_id', $content->id)->where('lesson_audio_slot', $slot)->first()?->setRelation('lesson', $content);
        }
        $content->loadMissing('audio');
        if ($content->audio !== null) {
            $content->audio->setRelation($content instanceof Lesson ? 'lesson' : 'question', $content);
            $content->audio->setRelation($content instanceof Lesson ? 'question' : 'lesson', null);
        }

        return $content->audio;
    }

    public function isCurrent(ContentAudio $audio): bool
    {
        $content = $audio->lesson ?? $audio->question;

        if ($content instanceof Lesson && $audio->lesson_audio_slot > count($content->exerciseQuestions())) {
            return false;
        }

        return $content !== null && $audio->source_hash === $this->sourceHash($content, $audio->lesson_audio_slot ?? 0);
    }

    public function isPlayable(?ContentAudio $audio): bool
    {
        return $audio !== null && $audio->status === 'ready' && $audio->reviewed_at !== null
            && $this->isCurrent($audio) && $audio->path !== null && Storage::disk('local')->exists($audio->path);
    }

    /** @param array{spoken_text: string, language: string, speed: int, action: string, version?: ?string} $data */
    public function save(Lesson|QuizQuestion $content, array $data, ?UploadedFile $upload = null, int $slot = 0): ContentAudio
    {
        if ($data['action'] === 'generate' && $data['language'] === 'ban') {
            throw ValidationException::withMessages(['language' => 'Untuk Bahasa Bali, unggah rekaman yang sudah diperiksa pengajar.']);
        }
        if ($data['action'] === 'generate' && in_array(config('services.tts.engine'), ['mms', 'openai', 'chatterbox'], true) && $data['language'] !== 'id') {
            throw ValidationException::withMessages(['language' => 'Model neural saat ini mendukung Bahasa Indonesia. Untuk bahasa lain, unggah rekaman.']);
        }
        $newPath = $upload?->store('content-audio', 'local');
        if ($upload !== null && ! is_string($newPath)) {
            throw new RuntimeException('Audio gagal disimpan.');
        }
        try {
            return DB::transaction(function () use ($content, $data, $newPath, $slot): ContentAudio {
                $content = $content->newQuery()->lockForUpdate()->findOrFail($content->id);
                $this->defaultText($content, $slot);
                $key = $content instanceof Lesson ? 'lesson_id' : 'quiz_question_id';
                $audio = ContentAudio::where($key, $content->id)->when($content instanceof Lesson, fn (Builder $query): Builder => $query->where('lesson_audio_slot', $slot))->lockForUpdate()->first();
                if ($audio !== null && ($data['version'] ?? null) !== $audio->version) {
                    abort(409, 'Audio sudah berubah. Muat ulang halaman sebelum menyimpan.');
                }
                if ($audio !== null && $data['action'] === 'generate' && $newPath === null
                    && $audio->engine === config('services.tts.engine', 'espeak')
                    && $audio->source_hash === $this->sourceHash($content, $slot)
                    && $audio->spoken_text === $data['spoken_text'] && $audio->language === $data['language'] && $audio->speed === (int) $data['speed']
                    && in_array($audio->status, ['queued', 'processing', 'review', 'ready'], true)) {
                    return $audio;
                }
                $oldPath = $audio?->path;
                $audio ??= new ContentAudio([$key => $content->id, 'lesson_audio_slot' => $slot]);
                $audio->fill([
                    'source_hash' => $this->sourceHash($content, $slot), 'version' => (string) Str::uuid(),
                    'spoken_text' => $data['spoken_text'], 'language' => $data['language'], 'speed' => (int) $data['speed'],
                    'engine' => $newPath !== null ? 'upload' : config('services.tts.engine', 'espeak'), 'path' => $newPath,
                    'status' => $newPath !== null ? 'review' : ($data['action'] === 'generate' ? ($this->isConfigured() ? 'queued' : 'unavailable') : 'draft'),
                    'reviewed_at' => null, 'generation_started_at' => null, 'generation_finished_at' => null,
                ])->save();
                DB::afterCommit(function () use ($oldPath, $newPath, $audio): void {
                    if ($oldPath !== null && $oldPath !== $newPath) {
                        Storage::disk('local')->delete($oldPath);
                    }
                    if ($audio->status === 'queued') {
                        try {
                            GenerateContentAudio::dispatch($audio->id, $audio->version);
                        } catch (Throwable) {
                            ContentAudio::whereKey($audio->id)->where('version', $audio->version)->update(['status' => 'failed']);
                        }
                    }
                });

                return $audio;
            });
        } catch (Throwable $exception) {
            if (is_string($newPath)) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }
    }

    public function approve(ContentAudio $audio, string $version): void
    {
        DB::transaction(function () use ($audio, $version): void {
            $audio = ContentAudio::whereKey($audio->id)->lockForUpdate()->firstOrFail();
            abort_unless($audio->version === $version && $audio->status === 'review' && $this->isCurrent($audio)
                && $audio->path !== null && Storage::disk('local')->exists($audio->path), 409, 'Audio belum siap ditinjau atau sudah kedaluwarsa.');
            $audio->update(['status' => 'ready', 'reviewed_at' => now()]);
        });
    }

    public function synthesize(ContentAudio $audio): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('TTS belum dikonfigurasi.');
        }
        $path = 'content-audio/'.Str::uuid().'.wav';
        $disk = Storage::disk('local');
        $disk->makeDirectory('content-audio');
        $metrics = null;
        try {
            if (config('services.tts.engine') === 'openai') {
                $request = Http::connectTimeout(5)->timeout(self::GENERATION_TIMEOUT)->accept('audio/wav')
                    ->sink($disk->path($path))->withOptions([
                        'allow_redirects' => false,
                        'progress' => static function (float $downloadTotal, float $downloadedBytes, float $uploadTotal, float $uploadedBytes): void {
                            if ($downloadTotal > self::MAX_AUDIO_BYTES || $downloadedBytes > self::MAX_AUDIO_BYTES) {
                                throw new RuntimeException('Audio TTS melebihi 64 MB.');
                            }
                        },
                    ]);
                if (filled(config('services.tts.key'))) {
                    $request = $request->withToken((string) config('services.tts.key'));
                }
                $response = $request->post(rtrim((string) config('services.tts.url'), '/').'/audio/speech', [
                    'model' => config('services.tts.model'), 'voice' => config('services.tts.voice'),
                    'input' => $audio->spoken_text, 'response_format' => 'wav', 'speed' => $audio->speed / 150,
                ]);
                if (! $response->successful()) {
                    throw new RuntimeException('Layanan TTS gagal menghasilkan audio.');
                }
            } elseif (config('services.tts.engine') === 'chatterbox' && config('services.tts.persistent')) {
                $metrics = app(ChatterboxProcess::class)->generate([
                    (string) config('services.tts.python'), '-u', base_path('app/Services/tts_chatterbox.py'),
                    'serve', '--model-dir', (string) config('services.tts.chatterbox_path'),
                    '--device', (string) config('services.tts.device'),
                ], ['TTS_CPU_THREADS' => (string) config('services.tts.cpu_threads'), 'PYTHONUTF8' => '1'], [
                    'text' => $audio->spoken_text, 'speed' => $audio->speed, 'destination' => $disk->path($path),
                    'reference' => filled(config('services.tts.reference_audio')) ? (string) config('services.tts.reference_audio') : null,
                ], self::GENERATION_TIMEOUT, function (array $progress) use ($audio): void {
                    Cache::put($this->progressKey($audio), [
                        ...array_intersect_key($progress, array_flip(['stage', 'completed_parts', 'total_parts', 'completed_chars', 'total_chars', 'generation_elapsed_seconds'])),
                        'reported_at' => now()->getTimestamp(),
                    ], now()->addHour());
                });
            } else {
                $environment = ['OMP_NUM_THREADS' => '2', 'MKL_NUM_THREADS' => '2', 'TOKENIZERS_PARALLELISM' => 'false', 'HF_HUB_OFFLINE' => '1', 'TRANSFORMERS_OFFLINE' => '1', 'PYTHONUTF8' => '1'];
                if (config('services.tts.engine') === 'chatterbox') {
                    $environment['TTS_CPU_THREADS'] = (string) config('services.tts.cpu_threads');
                }
                if (filled(config('services.tts.data_path'))) {
                    $environment['ESPEAK_DATA_PATH'] = (string) config('services.tts.data_path');
                }
                $command = match (config('services.tts.engine')) {
                    'chatterbox' => [(string) config('services.tts.python'), base_path('app/Services/tts_chatterbox.py'), 'synthesize', '--model-dir', (string) config('services.tts.chatterbox_path'), '--device', (string) config('services.tts.device'), '-v', $audio->language, '-s', (string) $audio->speed, '-w', $disk->path($path)],
                    'mms' => [(string) config('services.tts.python'), base_path('app/Services/tts_mms.py'), '--model-dir', (string) config('services.tts.model_path'), '-v', $audio->language, '-s', (string) $audio->speed, '-w', $disk->path($path)],
                    default => [(string) config('services.tts.binary'), '-v', $audio->language, '-s', (string) $audio->speed, '-b', '1', '-w', $disk->path($path), '--stdin'],
                };
                if (config('services.tts.engine') === 'chatterbox' && filled(config('services.tts.reference_audio'))) {
                    $command = [...$command, '--reference-audio', (string) config('services.tts.reference_audio')];
                }
                Process::timeout(config('services.tts.engine') === 'espeak' ? 90 : self::GENERATION_TIMEOUT)->env($environment)->input($audio->spoken_text)->run($command)->throw();
            }
            if (! $disk->exists($path) || $disk->size($path) < 44 || $disk->size($path) > self::MAX_AUDIO_BYTES) {
                throw new RuntimeException('TTS tidak menghasilkan audio yang valid.');
            }
            $stream = $disk->readStream($path);
            $header = is_resource($stream) ? fread($stream, 12) : '';
            if (is_resource($stream)) {
                fclose($stream);
            }
            if (substr($header, 0, 4) !== 'RIFF' || substr($header, 8, 4) !== 'WAVE') {
                throw new RuntimeException('Format audio TTS tidak valid.');
            }
            if ($metrics !== null) {
                Log::info('Chatterbox TTS selesai', ['audio_id' => $audio->id, ...array_intersect_key($metrics, array_flip([
                    'seconds', 'model_load_seconds', 'reference_seconds', 'generation_seconds', 'model_reused', 'threads', 'parts',
                ]))]);
            }

            return $path;
        } catch (Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }
    }

    private function progressKey(ContentAudio $audio): string
    {
        return 'content-audio-progress:'.$audio->id.':'.$audio->version;
    }

    public function resetGenerationProgress(ContentAudio $audio): void
    {
        Cache::forget($this->progressKey($audio));
    }

    /** @return array{stage: ?string, completed_parts: int, total_parts: int, percent: ?int, started_at: ?string, finished_at: ?string, elapsed_seconds: ?int, estimated_remaining_seconds: ?int} */
    public function generationProgress(ContentAudio $audio): array
    {
        $active = $audio->status === 'processing';
        $state = $active ? Cache::get($this->progressKey($audio), []) : [];
        $started = $audio->status === 'queued' ? null : $audio->generation_started_at;
        $finished = $audio->status === 'queued' ? null : $audio->generation_finished_at;
        $done = max(0, (int) ($state['completed_parts'] ?? 0));
        $total = max($done, (int) ($state['total_parts'] ?? 0));
        $eta = null;
        $chars = (int) ($state['completed_chars'] ?? 0);
        $remaining = (int) ($state['total_chars'] ?? 0) - $chars;
        if ($active && ($state['stage'] ?? null) === 'generating' && $chars > 0 && $remaining > 0) {
            $estimated = ((float) ($state['generation_elapsed_seconds'] ?? 0) / $chars) * $remaining;
            $estimated -= max(0, now()->getTimestamp() - (int) ($state['reported_at'] ?? now()->getTimestamp()));
            $eta = max(0, (int) ceil($estimated));
        }

        return [
            'stage' => $active ? ($state['stage'] ?? null) : null,
            'completed_parts' => $done, 'total_parts' => $total,
            'percent' => $total > 0 ? min(100, (int) floor(100 * $done / $total)) : null,
            'started_at' => $started?->toISOString(), 'finished_at' => $finished?->toISOString(),
            'elapsed_seconds' => $started ? max(0, ($finished ?? now())->getTimestamp() - $started->getTimestamp()) : null,
            'estimated_remaining_seconds' => $eta,
        ];
    }
}
