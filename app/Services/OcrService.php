<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OcrService
{
    public function extractText(string $path, string $name): string
    {
        try {
            $response = Http::baseUrl(rtrim((string) config('services.ocr.url'), '/'))
                ->withHeader('X-API-Key', (string) config('services.ocr.key'))
                ->timeout(90)
                ->attach('file', file_get_contents($path), $name)
                ->post('/ocr');
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Layanan OCR tidak dapat dihubungi.', previous: $exception);
        }

        if (! $response->successful() || ! $response->json('success') || ! is_string($response->json('text'))) {
            throw new RuntimeException('Dokumen gagal dibaca oleh layanan OCR.');
        }

        return trim($response->json('text'));
    }
}
