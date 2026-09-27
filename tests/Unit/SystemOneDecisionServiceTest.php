<?php

namespace Tests\Unit;

use App\Galika\Services\SystemOneDecisionService;
use App\Models\GalikaOpportunity;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SystemOneDecisionServiceTest extends TestCase
{
    public function test_off_mode_never_calls_provider(): void
    {
        config(['galika.system_one.mode' => 'off']);
        Http::preventStrayRequests();

        $result = app(SystemOneDecisionService::class)->classify(
            new GalikaOpportunity([
                'title' => 'AI Engineer',
                'employer' => 'Acme',
                'url' => 'https://example.test/jobs/1',
            ]),
            ['profile' => [], 'evidence' => []],
        );

        $this->assertNull($result);
    }

    public function test_shadow_mode_returns_typed_advisory_without_changing_gates(): void
    {
        config([
            'galika.system_one.mode' => 'shadow',
            'galika.system_one.base_url' => 'http://laya.test:8000',
            'galika.system_one.api_key' => 'test-key',
            'galika.system_one.timeout_seconds' => 1,
        ]);

        Http::fake([
            'http://laya.test:8000/v1/systemone' => Http::response([
                'answers' => [
                    'role_family' => [
                        'type' => 'choice',
                        'choice' => 'ai_engineering',
                        'confidence' => 0.94,
                        'probabilities' => ['ai_engineering' => 0.94, 'other' => 0.06],
                    ],
                    'requires_browser' => ['type' => 'noul', 'noul' => 0.92, 'confidence' => 0.92],
                ],
                'routing' => ['model' => 'english', 'reason' => 'English input'],
                'usage' => ['input_tokens' => 120, 'output_tokens' => 0],
            ], 200),
        ]);

        $result = app(SystemOneDecisionService::class)->classify(
            new GalikaOpportunity([
                'title' => 'Senior AI Engineer',
                'employer' => 'Acme',
                'location' => 'Remote',
                'url' => 'https://boards.greenhouse.io/acme/jobs/1',
                'description' => 'Build production AI agents and MLOps systems.',
                'source' => 'test',
            ]),
            [
                'profile' => ['headline' => 'AI Engineer'],
                'evidence' => [['domain' => 'SKILL', 'fact' => 'Python', 'verified' => true]],
            ],
        );

        $this->assertSame('laya', $result['provider']);
        $this->assertTrue($result['advisory_only']);
        $this->assertSame('ai_engineering', $result['answers']['role_family']['choice']);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'http://laya.test:8000/v1/systemone'
                && $request->hasHeader('Authorization', 'Bearer test-key')
                && data_get($payload, 'questions.application_readiness.type') === 'score'
                && data_get($payload, 'state.policy.incumbent_galika_gates_remain_authoritative') === true;
        });
    }

    public function test_provider_failure_fails_open(): void
    {
        config([
            'galika.system_one.mode' => 'shadow',
            'galika.system_one.base_url' => 'http://laya.test:8000',
        ]);

        Http::fake([
            'http://laya.test:8000/v1/systemone' => Http::response(['error' => 'unavailable'], 503),
        ]);

        $result = app(SystemOneDecisionService::class)->classify(
            new GalikaOpportunity([
                'title' => 'Data Engineer',
                'employer' => 'Acme',
                'url' => 'https://example.test/jobs/2',
            ]),
            ['profile' => [], 'evidence' => []],
        );

        $this->assertNull($result);
    }
}
