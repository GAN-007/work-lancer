<?php

namespace App\Services\Galika;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIClient
{
    public function structured(string $system, string $user, array $schema): array
    {
        $apiKey = config('services.openai.key');

        if (!$apiKey) {
            throw new RuntimeException('OPENAI_API_KEY is not configured.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(90)
            ->post('https://api.openai.com/v1/responses', [
                'model' => config('services.openai.model', 'gpt-5.6'),
                'input' => [
                    ['role' => 'system', 'content' => [['type' => 'input_text', 'text' => $system]]],
                    ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $user]]],
                ],
                'text' => [
                    'format' => [
                        'type' => 'json_schema',
                        'name' => 'galika_result',
                        'schema' => $schema,
                        'strict' => true,
                    ],
                ],
            ])
            ->throw()
            ->json();

        $text = data_get($response, 'output.0.content.0.text');

        if (!$text) {
            throw new RuntimeException('OpenAI returned no structured text output.');
        }

        $decoded = json_decode($text, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
