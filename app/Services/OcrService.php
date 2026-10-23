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

        $text = str_replace(["\r\n", "\r"], "\n", $response->json('text'));
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text) ?? $text;
        $lines = array_filter(explode("\n", $text), static function (string $line): bool {
            $words = preg_split('/\s+/u', trim($line), -1, PREG_SPLIT_NO_EMPTY);

            if (count($words) < 6) {
                return true;
            }

            $words = array_map(static fn (string $word): string => preg_replace('/[^\p{L}\p{N}]/u', '', $word) ?? '', $words);
            $shortWords = count(array_filter($words, static fn (string $word): bool => mb_strlen($word) <= 3));
            $uppercaseWords = count(array_filter($words, static fn (string $word): bool => preg_match('/^\p{Lu}{2,}$/u', $word) === 1));

            return ! ($shortWords / count($words) >= 0.8 && $uppercaseWords >= 2);
        });

        return trim(implode("\n", $lines));
    }
}
