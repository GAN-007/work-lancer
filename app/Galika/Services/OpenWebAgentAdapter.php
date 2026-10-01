<?php
namespace App\Galika\Services;

use App\Galika\Contracts\BrowserExecutionProvider;
use App\Models\GalikaApplication;
use App\Models\GalikaOpportunity;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenWebAgentAdapter implements BrowserExecutionProvider
{
    public function apply(
        GalikaApplication $application,
        GalikaOpportunity $opportunity,
        array $candidate,
        array $answers = [],
        array $attachments = []
    ): array {
        $endpoint = rtrim((string) config('services.open_web_agent.endpoint'), '/');
        if ($endpoint === '') {
            throw new RuntimeException('Open Web Agent endpoint missing');
        }

        $encodedAttachments = array_map(
            fn (array $attachment) => $this->encodeAttachment($attachment),
            $attachments
        );

        $response = $this->request((int) config('services.open_web_agent.timeout', 300))
            ->post($endpoint.'/v1/apply', [
                'application_id' => $application->id,
                'idempotency_key' => $application->application_key,
                'url' => $opportunity->official_url ?: $opportunity->url,
                'ats_type' => $application->ats_type,
                'candidate' => $candidate,
                'answers' => $answers,
                'attachments' => $encodedAttachments,
                'rules' => [
                    'require_confirmation' => true,
                    'do_not_invent' => true,
                    'stop_on_captcha' => true,
                    'stop_on_auth_failure' => true,
                    'preserve_evidence' => true,
                    'material_unknowns_require_human' => true,
                ],
            ]);

        if (!$response->successful()) {
            throw new RuntimeException(
                'Open Web Agent failed '.$response->status().': '.$response->body()
            );
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            throw new RuntimeException('Open Web Agent returned a non-JSON response');
        }

        return $payload;
    }

    public function health(): array
    {
        $endpoint = rtrim((string) config('services.open_web_agent.health_endpoint'), '/');
        if ($endpoint === '') {
            $base = rtrim((string) config('services.open_web_agent.endpoint'), '/');
            $endpoint = $base === '' ? '' : $base.'/health';
        }
        if ($endpoint === '') {
            return ['ok' => false, 'error' => 'Open Web Agent endpoint missing'];
        }

        try {
            $response = $this->request(20)->get($endpoint);
            return [
                'ok' => $response->successful(),
                'status' => $response->status(),
                'details' => $response->json(),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function request(int $timeout): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout(max(1, $timeout))
            ->retry(2, 250);

        $token = (string) config('services.open_web_agent.token');
        if ($token !== '') {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function encodeAttachment(array $attachment): array
    {
        $path = (string) ($attachment['path'] ?? '');
        if ($path === '' || !is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Application attachment is missing or unreadable: '.$path);
        }

        $size = filesize($path);
        $max = (int) config('services.open_web_agent.max_attachment_bytes', 12582912);
        if ($size === false || $size > $max) {
            throw new RuntimeException('Application attachment exceeds the configured upload limit');
        }

        $actualSha = hash_file('sha256', $path);
        $expectedSha = (string) ($attachment['sha256'] ?? '');
        if ($expectedSha !== '' && !hash_equals($expectedSha, $actualSha)) {
            throw new RuntimeException('Application attachment integrity check failed');
        }

        return [
            'kind' => (string) ($attachment['kind'] ?? 'attachment'),
            'filename' => basename($path),
            'mime' => mime_content_type($path) ?: 'application/octet-stream',
            'sha256' => $actualSha,
            'content_base64' => base64_encode((string) file_get_contents($path)),
        ];
    }
}
