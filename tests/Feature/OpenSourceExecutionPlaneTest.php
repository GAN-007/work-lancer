<?php
namespace Tests\Feature;

use App\Galika\Contracts\BrowserExecutionProvider;
use App\Galika\Services\BaserowReplicaService;
use App\Galika\Services\OpenWebAgentAdapter;
use App\Galika\Services\SourceBrokerService;
use App\Models\GalikaApplication;
use App\Models\GalikaOpportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenSourceExecutionPlaneTest extends TestCase
{
    use RefreshDatabase;

    public function test_browser_execution_contract_is_bound_to_local_open_web_agent(): void
    {
        $this->assertInstanceOf(OpenWebAgentAdapter::class,app(BrowserExecutionProvider::class));
    }

    public function test_open_web_agent_adapter_sends_verified_payload_and_hash_checked_attachment(): void
    {
        config([
            'services.open_web_agent.endpoint'=>'http://open-web-agent:8000',
            'services.open_web_agent.token'=>'local-secret',
            'services.open_web_agent.timeout'=>30,
        ]);

        Http::fake([
            'http://open-web-agent:8000/v1/apply'=>Http::response([
                'submitted'=>true,
                'confirmation'=>[
                    'page_url'=>'https://jobs.example.test/thanks',
                    'page_title'=>'Application received',
                    'visible_text'=>'Thank you. Your application has been received.',
                    'reference'=>'ABC-123',
                ],
                'confirmation_id'=>'ABC-123',
                'route'=>'open_web_agent',
                'notes'=>[],
            ],200),
        ]);

        $file=tempnam(sys_get_temp_dir(),'galika-test-');
        file_put_contents($file,'verified cv');

        $application=new GalikaApplication(['application_key'=>'apply-key-123','ats_type'=>'EMPLOYER_FORM']);
        $application->id=42;
        $opportunity=new GalikaOpportunity(['url'=>'https://jobs.example.test/apply']);

        $result=app(BrowserExecutionProvider::class)->apply(
            $application,
            $opportunity,
            ['profile'=>['name'=>'Candidate']],
            ['Authorized to work?'=>'Yes'],
            [['kind'=>'resume','path'=>$file,'sha256'=>hash_file('sha256',$file)]]
        );

        $this->assertTrue($result['submitted']);
        $this->assertSame('ABC-123',$result['confirmation_id']);

        Http::assertSent(function($request){
            $payload=$request->data();
            return $request->url()==='http://open-web-agent:8000/v1/apply'
                && $request->hasHeader('Authorization','Bearer local-secret')
                && data_get($payload,'idempotency_key')==='apply-key-123'
                && data_get($payload,'answers.Authorized to work?')==='Yes'
                && data_get($payload,'rules.do_not_invent')===true
                && data_get($payload,'attachments.0.sha256')===hash('sha256','verified cv');
        });

        @unlink($file);
    }

    public function test_projection_is_durable_in_postgres_when_baserow_is_disabled(): void
    {
        config(['services.baserow.enabled'=>false]);
        $user=User::factory()->create();

        app(BaserowReplicaService::class)->project($user->id,'APPLICATION_SUBMITTED',[
            'correlation_id'=>'corr-1',
            'application_id'=>99,
        ]);

        $this->assertDatabaseHas('galika_projection_records',[
            'user_id'=>$user->id,
            'correlation_id'=>'corr-1',
            'event_type'=>'APPLICATION_SUBMITTED',
            'mirror_state'=>'LOCAL_ONLY',
        ]);
    }

    public function test_outbox_command_delivers_baserow_destination_to_local_projection_without_baserow_dependency(): void
    {
        config(['services.baserow.enabled'=>false]);
        $user=User::factory()->create();

        DB::table('galika_outbox')->insert([
            'event_id'=>'evt-open-1',
            'user_id'=>$user->id,
            'destination'=>'baserow',
            'kind'=>'APPLICATION_SUBMITTED',
            'payload'=>json_encode(['correlation_id'=>'corr-outbox-1','application_id'=>1]),
            'status'=>'PENDING',
            'attempts'=>0,
            'available_at'=>now(),
            'leased_until'=>null,
            'last_error'=>null,
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        $this->artisan('galika:outbox --limit=10')->assertExitCode(0);

        $this->assertDatabaseHas('galika_outbox',['event_id'=>'evt-open-1','status'=>'DONE']);
        $this->assertDatabaseHas('galika_projection_records',[
            'user_id'=>$user->id,
            'correlation_id'=>'corr-outbox-1',
            'mirror_state'=>'LOCAL_ONLY',
        ]);
    }

    public function test_self_hosted_searxng_search_is_a_real_discovery_source(): void
    {
        config([
            'galika.discovery.queries'=>['AI Engineer'],
            'galika.discovery.open_search_enabled'=>true,
            'galika.discovery.open_search_results_per_query'=>5,
            'services.jobicy.endpoint'=>'https://jobicy.test/jobs',
            'services.open_web_agent.endpoint'=>'http://open-web-agent:8000',
        ]);

        Http::fake([
            'https://jobicy.test/jobs*'=>Http::response(['jobs'=>[]],200),
            'http://open-web-agent:8000/v1/search'=>Http::response([
                'results'=>[[
                    'title'=>'Senior AI Engineer - Acme',
                    'url'=>'https://jobs.acme.example/careers/ai-123',
                    'content'=>'Build production AI systems',
                ]],
                'suggestions'=>[],
            ],200),
        ]);

        $created=app(SourceBrokerService::class)->discover();

        $this->assertGreaterThanOrEqual(1,$created);
        $this->assertDatabaseHas('galika_opportunities',[
            'source'=>'open_search',
            'url'=>'https://jobs.acme.example/careers/ai-123',
        ]);
    }

    public function test_dynamic_reverification_can_fall_back_to_crawl4ai_fetch(): void
    {
        config([
            'galika.discovery.open_fetch_fallback'=>true,
            'services.open_web_agent.endpoint'=>'http://open-web-agent:8000',
        ]);

        Http::fake([
            'https://dynamic.example.test/jobs/1'=>Http::response('',403),
            'http://open-web-agent:8000/v1/fetch'=>Http::response([
                'url'=>'https://dynamic.example.test/jobs/1',
                'status_code'=>200,
                'markdown'=>'# Senior AI Engineer\nApplications are open. Apply now.',
                'links'=>[],
                'metadata'=>[],
            ],200),
        ]);

        $opportunity=GalikaOpportunity::create([
            'canonical_key'=>'dynamic-1',
            'source'=>'open_search',
            'employer'=>'Acme',
            'title'=>'Senior AI Engineer',
            'url'=>'https://dynamic.example.test/jobs/1',
            'discovered_at'=>now(),
        ]);

        $result=app(\App\Galika\Services\AuthoritativeReverificationService::class)->verify($opportunity);

        $this->assertTrue($result['open']);
        $this->assertSame('AUTHORITATIVE_OPEN_DYNAMIC',$result['reason']);
        $this->assertTrue((bool)$opportunity->fresh()->official_open);
    }
}
