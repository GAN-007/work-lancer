<?php

namespace App\Galika\Services;

use App\Models\GalikaOpportunity;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemOneDecisionService
{
    public function classify(GalikaOpportunity $opportunity, array $candidate): ?array
    {
        $mode = strtolower((string) config('galika.system_one.mode', 'off'));
        if ($mode === 'off') {
            return null;
        }

        $baseUrl = rtrim((string) config('galika.system_one.base_url', ''), '/');
        if ($baseUrl === '') {
            Log::debug('GALIKA System One skipped: no base URL configured.');
            return null;
        }

        $questions = [
            'role_family' => [
                'type' => 'choice',
                'instructions' => 'Which role family best describes this opportunity?',
                'criteria' => [
                    'data_science' => 'Data science, statistics, experimentation, predictive modelling',
                    'data_engineering' => 'Data pipelines, warehouses, ETL/ELT, analytics engineering',
                    'software_engineering' => 'Backend, frontend, full-stack, platform or application engineering',
                    'ai_engineering' => 'Machine learning engineering, LLMs, agents, MLOps or applied AI',
                    'technical_leadership' => 'Engineering management, technical lead, architecture or CTO-style work',
                    'other' => 'None of the listed role families',
                ],
            ],
            'ats_family' => [
                'type' => 'choice',
                'instructions' => 'Which application route or ATS family is most likely from the opportunity details?',
                'criteria' => [
                    'greenhouse' => 'Greenhouse or boards.greenhouse.io',
                    'lever' => 'Lever or jobs.lever.co',
                    'workday' => 'Workday or myworkdayjobs.com',
                    'ashby' => 'Ashby or ashbyhq.com',
                    'linkedin' => 'LinkedIn job application',
                    'smartrecruiters' => 'SmartRecruiters',
                    'jobvite' => 'Jobvite',
                    'icims' => 'iCIMS',
                    'successfactors' => 'SAP SuccessFactors',
                    'ceipal' => 'CEIPAL',
                    'employer_form' => 'Direct employer form or another ATS',
                ],
            ],
            'application_readiness' => [
                'type' => 'score',
                'instructions' => 'How ready is this opportunity for an application attempt using only the supplied verified evidence?',
                'criteria' => [
                    'insufficient information',
                    'requires enrichment or verification',
                    'mostly ready but may need one bounded decision',
                    'ready for the incumbent GALIKA gate sequence',
                ],
            ],
            'requires_browser' => [
                'type' => 'noul',
                'instructions' => 'Does this opportunity appear to require browser interaction for submission?',
            ],
            'manual_review_required' => [
                'type' => 'noul',
                'instructions' => 'Does the available information indicate that a material user decision or human review may be required before submission?',
            ],
        ];

        $state = [
            'opportunity' => [
                'title' => (string) $opportunity->title,
                'employer' => (string) $opportunity->employer,
                'location' => (string) ($opportunity->location ?? ''),
                'url' => (string) ($opportunity->official_url ?: $opportunity->url),
                'description' => mb_substr((string) ($opportunity->description ?? ''), 0, 8000),
                'source' => (string) ($opportunity->source ?? ''),
            ],
            'candidate' => [
                'profile' => $candidate['profile'] ?? [],
                'verified_evidence' => array_slice($candidate['evidence'] ?? [], 0, 100),
            ],
            'policy' => [
                'advisory_only' => true,
                'must_not_infer_material_answers' => true,
                'incumbent_galika_gates_remain_authoritative' => true,
            ],
        ];

        $timeout = max(0.1, (float) config('galika.system_one.timeout_seconds', 1.5));
        $started = microtime(true);

        try {
            $request = Http::acceptJson()
                ->asJson()
                ->timeout($timeout)
                ->connectTimeout(min($timeout, 1.0));

            $apiKey = trim((string) config('galika.system_one.api_key', ''));
            if ($apiKey !== '') {
                $request = $request->withToken($apiKey);
            }

            $response = $request->post($baseUrl.'/v1/systemone', [
                'state' => $state,
                'questions' => $questions,
            ]);

            if (! $response->successful()) {
                Log::warning('GALIKA System One request failed.', [
                    'status' => $response->status(),
                    'mode' => $mode,
                ]);
                return null;
            }

            $payload = $response->json();
            if (! is_array($payload) || ! is_array($payload['answers'] ?? null)) {
                Log::warning('GALIKA System One returned an invalid payload.', ['mode' => $mode]);
                return null;
            }

            return [
                'provider' => 'laya',
                'mode' => $mode,
                'routing' => $payload['routing'] ?? null,
                'answers' => $payload['answers'],
                'usage' => $payload['usage'] ?? null,
                'latency_ms' => (int) round((microtime(true) - $started) * 1000),
                'advisory_only' => true,
            ];
        } catch (ConnectionException $exception) {
            Log::notice('GALIKA System One unavailable; incumbent flow continues.', [
                'message' => $exception->getMessage(),
            ]);
            return null;
        } catch (Throwable $exception) {
            Log::warning('GALIKA System One failed open; incumbent flow continues.', [
                'type' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
            return null;
        }
    }
}
