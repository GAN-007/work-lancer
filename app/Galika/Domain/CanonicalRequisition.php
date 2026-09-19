<?php

namespace App\Galika\Domain;

use Carbon\CarbonImmutable;

final class CanonicalRequisition
{
    public function __construct(
        public readonly string $employer,
        public readonly string $title,
        public readonly string $canonicalUrl,
        public readonly ?string $requisitionId,
        public readonly string $source,
        public readonly CarbonImmutable $discoveredAt,
        public readonly ?CarbonImmutable $publishedAt,
        public readonly ?string $location = null,
        public readonly ?string $remotePolicy = null,
    ) {}

    public function key(): string
    {
        $employer = mb_strtolower(trim(preg_replace('/\s+/', ' ', $this->employer)));
        $req = $this->requisitionId ?: $this->normalizedUrl();
        return hash('sha256', $employer.'|'.$req);
    }

    public function normalizedUrl(): string
    {
        $parts = parse_url($this->canonicalUrl);
        if (!$parts || empty($parts['host'])) {
            return trim($this->canonicalUrl);
        }
        $scheme = strtolower($parts['scheme'] ?? 'https');
        $host = strtolower($parts['host']);
        $path = rtrim($parts['path'] ?? '/', '/');
        return $scheme.'://'.$host.($path ?: '/');
    }

    public function postingAgeHours(CarbonImmutable $now): ?float
    {
        if (!$this->publishedAt) {
            return null;
        }
        return $this->publishedAt->floatDiffInHours($now, false);
    }
}
