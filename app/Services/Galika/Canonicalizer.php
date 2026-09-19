<?php

namespace App\Services\Galika;

use Illuminate\Support\Str;

class Canonicalizer
{
    public function key(string $employer, string $role, ?string $requisitionId, ?string $authoritativeUrl): string
    {
        $parts = [
            Str::lower(trim($employer)),
            Str::lower(trim($role)),
            Str::lower(trim((string) $requisitionId)),
            Str::lower(trim((string) $authoritativeUrl)),
        ];

        return hash('sha256', implode('|', $parts));
    }
}
