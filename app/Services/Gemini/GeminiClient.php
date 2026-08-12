<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around Gemini's generateContent REST endpoint. Deliberately
 * request-per-call rather than a persistent chat/tool-use loop — every
 * caller today is a one-shot "summarize these figures" prompt, not a
 * multi-turn assistant.
 */
class GeminiClient
{
    public function generate(string $prompt, ?string $systemInstruction = null): string
    {
        return trim($this->request($prompt, $systemInstruction));
    }

    /**
     * Same call, but constrains the model to return JSON matching the given
     * schema (Gemini's `responseSchema`, an OpenAPI-subset shape) and
     * decodes it. Used for the report-summary features so the UI can render
     * distinct sections (insights/warnings/recommendations) instead of one
     * unstructured paragraph.
     *
     * @param  array<string, mixed>  $responseSchema
     * @return array<string, mixed>
     */
    public function generateStructured(string $prompt, string $systemInstruction, array $responseSchema): array
    {
        $text = $this->request($prompt, $systemInstruction, [
            'responseMimeType' => 'application/json',
            'responseSchema' => $responseSchema,
        ]);

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid JSON.');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>|null  $generationConfig
     */
    protected function request(string $prompt, ?string $systemInstruction, ?array $generationConfig = null): string
    {
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model');

        if (blank($apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
            ],
        ];

        if ($systemInstruction !== null) {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        if ($generationConfig !== null) {
            $payload['generationConfig'] = $generationConfig;
        }

        $response = Http::timeout(20)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gemini request failed ('.$response->status().'): '.$response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (blank($text)) {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return $text;
    }
}
