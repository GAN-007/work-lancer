<?php

namespace App\Services\Galika;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class TinyFishClient
{
    public function start(string $url, string $goal): array
    {
        $apiKey = config('services.tinyfish.key');
        $baseUrl = rtrim((string) config('services.tinyfish.base_url'), '/');

        if (!$apiKey || !$baseUrl) {
            throw new RuntimeException('TinyFish integration is not configured.');
        }

        $payload = [
            'url' => $url,
            'goal' => $goal,
            'use_profile' => (bool) config('services.tinyfish.profile_id'),
        ];

        if (config('services.tinyfish.profile_id')) {
            $payload['profile_id'] = config('services.tinyfish.profile_id');
        }

        return Http::withToken($apiKey)
            ->acceptJson()
            ->timeout(30)
            ->post($baseUrl.'/run', $payload)
            ->throw()
            ->json();
    }
}
