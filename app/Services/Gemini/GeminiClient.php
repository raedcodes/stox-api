<?php

namespace App\Services\Gemini;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin wrapper around Gemini's generateContent REST endpoint.
 */
class GeminiClient
{
    public function generate(string $prompt, ?string $systemInstruction = null): string
    {
        $turn = $this->send([
            ['role' => 'user', 'parts' => [['text' => $prompt]]],
        ], $systemInstruction);

        $text = $turn['parts'][0]['text'] ?? null;

        if (blank($text)) {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        return trim($text);
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
        $turn = $this->send([
            ['role' => 'user', 'parts' => [['text' => $prompt]]],
        ], $systemInstruction, null, [
            'responseMimeType' => 'application/json',
            'responseSchema' => $responseSchema,
        ]);

        $text = $turn['parts'][0]['text'] ?? null;

        if (blank($text)) {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid JSON.');
        }

        return $decoded;
    }

    /**
     * One turn of a multi-turn, tool-calling conversation. Returns the raw
     * model turn (`{role: "model", parts: [...]}`) rather than extracted
     * text, since the caller must inspect `parts` for a `functionCall`
     * versus a final `text` answer and drive the tool-execution loop itself
     * — this client only speaks the Gemini wire format, it doesn't know
     * what any tool actually does.
     *
     * @param  array<int, array<string, mixed>>  $contents  Full conversation history so far, Gemini's own {role, parts} shape.
     * @param  array<int, array<string, mixed>>  $tools  Function declarations (name/description/parameters JSON Schema).
     * @return array<string, mixed>
     */
    public function sendConversation(array $contents, string $systemInstruction, array $tools): array
    {
        return $this->send($contents, $systemInstruction, $tools);
    }

    /**
     * @param  array<int, array<string, mixed>>  $contents
     * @param  array<int, array<string, mixed>>|null  $tools
     * @param  array<string, mixed>|null  $generationConfig
     * @return array<string, mixed> The model's turn: {role, parts}.
     */
    protected function send(array $contents, ?string $systemInstruction, ?array $tools = null, ?array $generationConfig = null): array
    {
        $apiKey = config('services.gemini.key');
        $model = config('services.gemini.model');

        if (blank($apiKey)) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $payload = ['contents' => array_map($this->normalizeFunctionCallArgs(...), $contents)];

        if ($systemInstruction !== null) {
            $payload['systemInstruction'] = ['parts' => [['text' => $systemInstruction]]];
        }

        if ($tools !== null) {
            $payload['tools'] = [['function_declarations' => $tools]];
        }

        if ($generationConfig !== null) {
            $payload['generationConfig'] = $generationConfig;
        }

        $response = Http::timeout(30)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", $payload);

        if ($response->failed()) {
            throw new RuntimeException('Gemini request failed ('.$response->status().'): '.$response->body());
        }

        $turn = $response->json('candidates.0.content');

        if (! is_array($turn)) {
            throw new RuntimeException('Gemini returned no response candidate.');
        }

        return $turn;
    }

    /**
     * A tool call with no arguments decodes from Gemini's `args: {}` into
     * PHP's `[]` — indistinguishable from an empty *list*. Once that turn
     * is stored as conversation history and replayed back to Gemini on a
     * later request, re-encoding `[]` as JSON produces a list where
     * Gemini's proto schema requires a map, and the request is rejected
     * ("Proto field is not repeating, cannot start list"). Casting empty
     * args to `(object) []` guarantees they round-trip as `{}` on the wire.
     *
     * Applied only to the outgoing request payload, never to a turn
     * returned to the caller — callers (and their stored history) always
     * see plain PHP arrays, which is what they read `functionCall.args`
     * as. Mutating the returned value instead would hand a `stdClass`
     * where the caller expects an array.
     *
     * @param  array<string, mixed>  $turn
     * @return array<string, mixed>
     */
    protected function normalizeFunctionCallArgs(array $turn): array
    {
        foreach ($turn['parts'] ?? [] as $index => $part) {
            if (isset($part['functionCall']['args']) && $part['functionCall']['args'] === []) {
                $turn['parts'][$index]['functionCall']['args'] = (object) [];
            }
        }

        return $turn;
    }
}
