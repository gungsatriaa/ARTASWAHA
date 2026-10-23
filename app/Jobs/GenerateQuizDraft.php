<?php

namespace App\Jobs;

use App\Models\QuizDraft as QuizDraftModel;
use App\Services\OcrService;
use App\Services\QuizDraftGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GenerateQuizDraft implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 240;

    public function __construct(public int $draftId) {}

    public function handle(): void
    {
        $draft = QuizDraftModel::findOrFail($this->draftId);
        $draft->update(['status' => 'processing']);
        try {
            $disk = Storage::disk('local');
            $text = app(OcrService::class)->extractText($disk->path($draft->source_path), basename($draft->source_path));
            if ($text === '') {
                throw new \RuntimeException('OCR tidak menemukan teks.');
            }
            $draft->update(['source_text' => $text, 'questions' => app(QuizDraftGenerator::class)->generate($text), 'status' => 'completed']);
        } catch (Throwable $exception) {
            Log::warning('Quiz draft generation failed.', ['draft_id' => $draft->id, 'exception' => $exception->getMessage()]);
            $draft->update(['status' => 'failed', 'error' => 'Dokumen gagal diproses. Periksa file lalu coba lagi.']);
        } finally {
            Storage::disk('local')->delete($draft->source_path);
        }
    }
}
