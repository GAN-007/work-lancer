<?php

namespace App\Services\Galika;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GmailEvidenceService
{
    public function search(string $query): array
    {
        $token = config('services.google.access_token');

        if (!$token) {
            throw new RuntimeException('Google access token is not configured.');
        }

        return Http::withToken($token)
            ->acceptJson()
            ->timeout(30)
            ->get('https://gmail.googleapis.com/gmail/v1/users/me/messages', [
                'q' => $query,
                'maxResults' => 100,
            ])
            ->throw()
            ->json();
    }
}
