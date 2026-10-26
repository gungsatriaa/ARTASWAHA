<?php

namespace App\Services;

use App\Jobs\GenerateContentAudio;
use App\Models\ContentAudio;
use App\Models\Lesson;
use App\Models\QuizQuestion;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ContentAudioService
{
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

    public function sourceHash(Lesson|QuizQuestion $content): string
    {
        return hash('sha256', json_encode($content instanceof Lesson
            ? [$content->title, $content->content]
            : [$content->prompt, $content->options], JSON_THROW_ON_ERROR));
    }

    public function defaultText(Lesson|QuizQuestion $content): string
    {
        $text = $content instanceof Lesson ? $content->title.".\n".$content->content : $content->prompt;
        if ($content instanceof QuizQuestion) {
            foreach ($content->options as $index => $option) {
                $text .= "\n".chr(65 + $index).'. '.$option;
            }
        }

        /** ponytail: Narration is limited to 8000 characters; split long modules into audio chapters if needed. */
        return mb_substr($text, 0, 8000);
    }

    public function forContent(Lesson|QuizQuestion $content): ?ContentAudio
    {
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

        return $content !== null && $audio->source_hash === $this->sourceHash($content);
    }

    public function isPlayable(?ContentAudio $audio): bool
    {
        return $audio !== null && $audio->status === 'ready' && $audio->reviewed_at !== null
            && $this->isCurrent($audio) && $audio->path !== null && Storage::disk('local')->exists($audio->path);
    }

    /** @param array{spoken_text: string, language: string, speed: int, action: string, version?: ?string} $data */
    public function save(Lesson|QuizQuestion $content, array $data, ?UploadedFile $upload = null): ContentAudio
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
            return DB::transaction(function () use ($content, $data, $newPath): ContentAudio {
                $content = $content->newQuery()->lockForUpdate()->findOrFail($content->id);
                $key = $content instanceof Lesson ? 'lesson_id' : 'quiz_question_id';
                $audio = ContentAudio::where($key, $content->id)->lockForUpdate()->first();
                if ($audio !== null && ($data['version'] ?? null) !== $audio->version) {
                    abort(409, 'Audio sudah berubah. Muat ulang halaman sebelum menyimpan.');
                }
                if ($audio !== null && $data['action'] === 'generate' && $newPath === null
                    && $audio->engine === config('services.tts.engine', 'espeak')
                    && $audio->source_hash === $this->sourceHash($content)
                    && $audio->spoken_text === $data['spoken_text'] && $audio->language === $data['language'] && $audio->speed === (int) $data['speed']
                    && in_array($audio->status, ['queued', 'processing', 'review', 'ready'], true)) {
                    return $audio;
                }
                $oldPath = $audio?->path;
                $audio ??= new ContentAudio([$key => $content->id]);
                $audio->fill([
                    'source_hash' => $this->sourceHash($content), 'version' => (string) Str::uuid(),
                    'spoken_text' => $data['spoken_text'], 'language' => $data['language'], 'speed' => (int) $data['speed'],
                    'engine' => $newPath !== null ? 'upload' : config('services.tts.engine', 'espeak'), 'path' => $newPath,
                    'status' => $newPath !== null ? 'review' : ($data['action'] === 'generate' ? ($this->isConfigured() ? 'queued' : 'unavailable') : 'draft'),
                    'reviewed_at' => null,
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
        try {
            if (config('services.tts.engine') === 'openai') {
                $request = Http::connectTimeout(5)->timeout(240)->accept('audio/wav')
                    ->sink($disk->path($path))->withOptions([
                        'allow_redirects' => false,
                        'progress' => static function (float $downloadTotal, float $downloadedBytes, float $uploadTotal, float $uploadedBytes): void {
                            if ($downloadTotal > 10485760 || $downloadedBytes > 10485760) {
                                throw new RuntimeException('Audio TTS melebihi 10 MB.');
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
                $result = Process::timeout(config('services.tts.engine') === 'espeak' ? 90 : 240)->env($environment)->input($audio->spoken_text)->run($command);
                if (! $result->successful()) {
                    throw new RuntimeException('TTS gagal menghasilkan audio.');
                }
            }
            if (! $disk->exists($path) || $disk->size($path) < 44 || $disk->size($path) > 10485760) {
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

            return $path;
        } catch (Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }
    }
}
