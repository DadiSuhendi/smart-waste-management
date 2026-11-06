<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class ImageClassifier
{
    public function predict(UploadedFile $photo): ?array
    {
        try {
            $response = Http::connectTimeout(2)->timeout(20)
                ->withHeaders(['X-API-Key' => (string) config('services.classifier.key')])
                ->attach('file', file_get_contents($photo->getRealPath()), $photo->hashName())
                ->post(rtrim(config('services.classifier.url'), '/').'/predict');
            $result = $response->json();
            if (! $response->successful() || ! in_array($result['category'] ?? null, ['organic', 'plastic', 'paper'], true)
                || ! is_numeric($result['confidence'] ?? null) || $result['confidence'] < 0 || $result['confidence'] > 1) {
                return null;
            }

            return $result;
        } catch (ConnectionException) {
            return null;
        }
    }
}
