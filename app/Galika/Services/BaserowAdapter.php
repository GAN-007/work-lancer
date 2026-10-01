<?php
namespace App\Galika\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BaserowAdapter
{
    public function configured(): bool
    {
        return (bool) config('services.baserow.enabled')
            && (string) config('services.baserow.url') !== ''
            && (string) config('services.baserow.token') !== ''
            && (string) config('services.baserow.table_id') !== '';
    }

    public function health(): array
    {
        if (!$this->configured()) {
            return ['ok' => false, 'error' => 'Baserow mirror is not configured'];
        }

        try {
            $tableId = (string) config('services.baserow.table_id');
            $response = $this->request()->get($this->url("/api/database/fields/table/{$tableId}/"));

            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    public function upsert(string $mergeField, array $fields): array
    {
        if (!$this->configured()) {
            return ['mirrored' => false, 'reason' => 'not_configured'];
        }
        if (!array_key_exists($mergeField, $fields)) {
            throw new RuntimeException("Baserow merge field {$mergeField} is missing");
        }

        $tableId = (string) config('services.baserow.table_id');
        $list = $this->request()->get(
            $this->url("/api/database/rows/table/{$tableId}/"),
            [
                'user_field_names' => 'true',
                'search' => (string) $fields[$mergeField],
                'size' => 200,
            ]
        );

        if (!$list->successful()) {
            throw new RuntimeException('Baserow row lookup failed '.$list->status().': '.$list->body());
        }

        $match = collect($list->json('results', []))
            ->first(fn (array $row) => (string) ($row[$mergeField] ?? '') === (string) $fields[$mergeField]);

        if ($match && isset($match['id'])) {
            $response = $this->request()->patch(
                $this->url("/api/database/rows/table/{$tableId}/{$match['id']}/?user_field_names=true"),
                $fields
            );
        } else {
            $response = $this->request()->post(
                $this->url("/api/database/rows/table/{$tableId}/?user_field_names=true"),
                $fields
            );
        }

        if (!$response->successful()) {
            throw new RuntimeException('Baserow upsert failed '.$response->status().': '.$response->body());
        }

        return ['mirrored' => true, 'row' => $response->json()];
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->withHeaders(['Authorization' => 'Token '.config('services.baserow.token')])
            ->timeout(30)
            ->retry(2, 250);
    }

    private function url(string $path): string
    {
        return rtrim((string) config('services.baserow.url'), '/').$path;
    }
}
