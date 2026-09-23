<?php
namespace Tests\Feature;

use App\Galika\Services\CanonicalizationService;
use App\Galika\Services\PlatformCircuitBreaker;
use App\Galika\Services\PolicyEngine;
use App\Models\GalikaApplication;
use App\Models\GalikaDecision;
use App\Models\GalikaEmailRoute;
use App\Models\GalikaOpportunity;
use App\Models\GalikaPolicy;
use App\Models\GalikaProfile;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GalikaLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_opportunity_prevents_duplicate_application(): void
    {
        $user=User::factory()->create();
        $o=GalikaOpportunity::create(['canonical_key'=>'k','source'=>'test','employer'=>'Acme','title'=>'AI Engineer','url'=>'https://example.test/job/1','discovered_at'=>now()]);
        GalikaApplication::create(['user_id'=>$user->id,'opportunity_id'=>$o->id,'application_key'=>'a','status'=>'SUBMITTED_CONFIRMED']);
        $this->expectException(QueryException::class);
        GalikaApplication::create(['user_id'=>$user->id,'opportunity_id'=>$o->id,'application_key'=>'b','status'=>'DISCOVERED']);
    }

    public function test_material_decision_and_delivery_tables_migrate(): void
    {
        $this->assertTrue(\Schema::hasTable('galika_decisions'));
        $this->assertTrue(\Schema::hasTable('galika_execution_work_items'));
        $this->assertTrue(\Schema::hasTable('galika_email_routes'));
        $this->assertTrue(\Schema::hasTable('galika_delivery_events'));
        $this->assertTrue(\Schema::hasTable('galika_profiles'));
    }

    public function test_cross_source_fingerprint_is_stable(): void
    {
        $svc=app(CanonicalizationService::class);
        $a=$svc->fingerprint(['employer'=>'Acme','title'=>'Senior AI Engineer','requisition_id'=>'123','location'=>'Remote']);
        $b=$svc->fingerprint(['companyName'=>'Acme','jobTitle'=>'Senior AI Engineer','external_id'=>'123','jobGeo'=>'Remote']);
        $this->assertSame($a,$b);
    }

    public function test_policy_engine_blocks_matching_deny_rule(): void
    {
        $u=User::factory()->create();
        GalikaProfile::create(['user_id'=>$u->id,'timezone'=>'UTC']);
        GalikaPolicy::create(['user_id'=>$u->id,'policy_key'=>'deny-gambling','operator'=>'DENY','rule'=>['kind'=>'industry','value'=>'gambling']]);
        $r=app(PolicyEngine::class)->evaluate($u->id,['title'=>'Platform Engineer','description'=>'Build gambling systems']);
        $this->assertFalse($r['allowed']);
    }

    public function test_circuit_breaker_opens_after_failures(): void
    {
        $c=app(PlatformCircuitBreaker::class);
        $c->failure('x','e1',2);$this->assertTrue($c->available('x'));
        $c->failure('x','e2',2);$this->assertFalse($c->available('x'));
        $c->success('x');$this->assertTrue($c->available('x'));
    }

    public function test_galika_dashboard_requires_authentication(): void
    {
        $this->get('/galika')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_open_command_center(): void
    {
        $u=User::factory()->create();
        $this->actingAs($u)->get('/galika')->assertOk()->assertSee('Career Command Center');
    }

    public function test_profile_can_enable_and_pause_autonomy(): void
    {
        $u=User::factory()->create();
        $this->actingAs($u)->post('/galika/profile',[
            'headline'=>'AI Engineer','location'=>'Nairobi','country'=>'Kenya','timezone'=>'Africa/Nairobi',
            'minimum_match_score'=>75,'autonomous_apply_enabled'=>1,'pause_all_execution'=>1,'review_mode'=>'MATERIAL_ONLY'
        ])->assertRedirect();
        $this->assertDatabaseHas('galika_profiles',['user_id'=>$u->id,'autonomous_apply_enabled'=>1,'pause_all_execution'=>1,'minimum_match_score'=>75]);
    }

    public function test_decision_resolution_is_user_scoped(): void
    {
        $u=User::factory()->create();$other=User::factory()->create();
        $o=GalikaOpportunity::create(['canonical_key'=>'d','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://e.test','discovered_at'=>now()]);
        $a=GalikaApplication::create(['user_id'=>$u->id,'opportunity_id'=>$o->id,'application_key'=>'d1','status'=>'BLOCKED_REQUIRES_USER']);
        $d=GalikaDecision::create(['application_id'=>$a->id,'decision_type'=>'MATERIAL_ANSWER','question'=>'Authorized?','status'=>'OPEN']);
        $this->actingAs($other)->post("/galika/decisions/{$d->id}",['answer'=>'Yes'])->assertForbidden();
    }

    public function test_email_route_can_hold_hard_bounce_suppression(): void
    {
        $u=User::factory()->create();
        GalikaEmailRoute::create(['user_id'=>$u->id,'employer'=>'Acme','email'=>'jobs@acme.test','domain'=>'acme.test','state'=>'HARD_BOUNCED','do_not_retry'=>true]);
        $this->assertDatabaseHas('galika_email_routes',['email'=>'jobs@acme.test','do_not_retry'=>1,'state'=>'HARD_BOUNCED']);
    }


    public function test_user_can_store_encrypted_api_connection(): void
    {
        $u=User::factory()->create();
        $vault=app(\App\Galika\Services\ConnectionVault::class);
        $vault->store($u->id,'openai','api_key',['api_key'=>'sk-test-secret']);
        $this->assertDatabaseHas('galika_connections',['user_id'=>$u->id,'provider'=>'openai','auth_type'=>'api_key']);
        $raw=\DB::table('galika_connections')->where('user_id',$u->id)->where('provider','openai')->value('api_key');
        $this->assertNotSame('sk-test-secret',$raw);
        $this->assertSame('sk-test-secret',$vault->credential($u->id,'openai','api_key'));
    }

    public function test_gmail_oauth_start_redirects_with_state(): void
    {
        config([
            'services.oauth.gmail'=>[
                'client_id'=>'client',
                'client_secret'=>'secret',
                'redirect_uri'=>'http://localhost/galika/oauth/gmail/callback',
                'authorize_url'=>'https://accounts.google.com/o/oauth2/v2/auth',
                'token_url'=>'https://oauth2.googleapis.com/token',
                'scopes'=>['openid','email'],
            ]
        ]);
        $u=User::factory()->create();
        $response=$this->actingAs($u)->get('/galika/oauth/gmail');
        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
        $this->assertNotNull(session('oauth_state_gmail'));
    }

    public function test_cv_confirmation_promotes_only_selected_facts(): void
    {
        $u=User::factory()->create();
        $doc=\App\Models\GalikaDocument::create([
            'user_id'=>$u->id,'kind'=>'CV','disk'=>'local','path'=>'x','original_name'=>'cv.txt','mime'=>'text/plain',
            'size'=>10,'sha256'=>str_repeat('a',64),'extracted_text'=>'text',
            'extracted_facts'=>[
                ['domain'=>'SKILL','fact'=>'Python','confidence'=>0.99,'tags'=>['python']],
                ['domain'=>'SKILL','fact'=>'Rust','confidence'=>0.50,'tags'=>['rust']],
            ],'confirmed'=>false
        ]);
        app(\App\Galika\Services\CvIngestionService::class)->confirm($doc,[0]);
        $this->assertDatabaseHas('galika_evidence',['user_id'=>$u->id,'fact'=>'Python','verified'=>1]);
        $this->assertDatabaseMissing('galika_evidence',['user_id'=>$u->id,'fact'=>'Rust']);
    }

    public function test_ats_router_detects_supported_platforms(): void
    {
        $router=app(\App\Galika\Services\AtsRouter::class);
        $o=new GalikaOpportunity(['url'=>'https://boards.greenhouse.io/acme/jobs/1']);$this->assertSame('GREENHOUSE',$router->detect($o));
        $o=new GalikaOpportunity(['url'=>'https://jobs.lever.co/acme/1']);$this->assertSame('LEVER',$router->detect($o));
        $o=new GalikaOpportunity(['url'=>'https://acme.wd5.myworkdayjobs.com/job']);$this->assertSame('WORKDAY',$router->detect($o));
        $o=new GalikaOpportunity(['url'=>'https://www.linkedin.com/jobs/view/1']);$this->assertSame('LINKEDIN',$router->detect($o));
        $o=new GalikaOpportunity(['url'=>'https://jobs.acme.test/apply']);$this->assertSame('EMPLOYER_FORM',$router->detect($o));
    }

    public function test_wealth_item_can_be_created_from_ui(): void
    {
        $u=User::factory()->create();
        $this->actingAs($u)->post('/galika/wealth',[
            'lane'=>'CONSULTING','title'=>'AI analytics project','counterparty'=>'Acme','currency'=>'KES'
        ])->assertRedirect();
        $this->assertDatabaseHas('galika_wealth_items',['user_id'=>$u->id,'lane'=>'CONSULTING','title'=>'AI analytics project','state'=>'DISCOVERED']);
    }

    public function test_canary_requires_real_pipeline_readiness(): void
    {
        $u=User::factory()->create();
        GalikaProfile::create(['user_id'=>$u->id,'timezone'=>'UTC','autonomous_apply_enabled'=>true]);
        $run=app(\App\Galika\Services\CanaryService::class)->run($u->id);
        $this->assertSame('FAILED',$run->state);
        $this->assertTrue(collect($run->steps)->contains(fn($s)=>$s['step']==='verified_documents'&&!$s['ok']));
    }

    public function test_supported_oauth_providers_build_authorization_redirects(): void
    {
        foreach([
            'airtable'=>['https://airtable.com/oauth2/v1/authorize','https://airtable.com/oauth2/v1/token'],
            'linkedin'=>['https://www.linkedin.com/oauth/v2/authorization','https://www.linkedin.com/oauth/v2/accessToken'],
            'lever'=>['https://auth.lever.co/authorize','https://auth.lever.co/oauth/token'],
        ] as $provider=>$urls){
            config(["services.oauth.{$provider}"=>[
                'client_id'=>'client-'.$provider,
                'client_secret'=>'secret-'.$provider,
                'redirect_uri'=>"http://localhost/galika/oauth/{$provider}/callback",
                'authorize_url'=>$urls[0],
                'token_url'=>$urls[1],
                'scopes'=>['openid'],
            ]]);
            $u=User::factory()->create();
            $response=$this->actingAs($u)->get("/galika/oauth/{$provider}");
            $response->assertRedirect();
            $this->assertStringContainsString(parse_url($urls[0],PHP_URL_HOST),$response->headers->get('Location'));
            $this->assertNotNull(session("oauth_state_{$provider}"));
        }
    }

    public function test_oauth_refresh_rotates_access_token_and_preserves_refresh_token_when_provider_omits_new_one(): void
    {
        $u=User::factory()->create();
        config(['services.oauth.gmail'=>[
            'client_id'=>'client','client_secret'=>'secret',
            'redirect_uri'=>'http://localhost/galika/oauth/gmail/callback',
            'authorize_url'=>'https://accounts.google.com/o/oauth2/v2/auth',
            'token_url'=>'https://oauth2.googleapis.com/token',
            'scopes'=>['openid','email'],
        ]]);
        $vault=app(\App\Galika\Services\ConnectionVault::class);
        $vault->store($u->id,'gmail','oauth',[
            'access_token'=>'old-access','refresh_token'=>'refresh-one','expires_at'=>now()->subMinute(),'scopes'=>['openid']
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/token'=>Http::response([
                'access_token'=>'new-access','expires_in'=>3600,'scope'=>'openid email'
            ],200),
        ]);

        $new=app(\App\Galika\Services\OAuthService::class)->refresh($u->id,'gmail');
        $this->assertSame('new-access',$new);
        $this->assertSame('refresh-one',$vault->credential($u->id,'gmail','refresh_token'));
        $this->assertSame('new-access',$vault->credential($u->id,'gmail','access_token'));
    }

    public function test_connections_page_uses_connect_buttons_for_oauth_providers(): void
    {
        $u=User::factory()->create();
        $this->actingAs($u)->get('/galika/connections')
            ->assertOk()
            ->assertSee('Connect Gmail')
            ->assertSee('Connect Airtable')
            ->assertSee('Connect Linkedin')
            ->assertSee('Connect Lever');
    }


    public function test_connected_airtable_user_can_discover_and_select_base(): void
    {
        $u=User::factory()->create();
        $vault=app(\App\Galika\Services\ConnectionVault::class);
        $vault->store($u->id,'airtable','oauth',[
            'access_token'=>'air-access','refresh_token'=>'air-refresh','expires_at'=>now()->addHour(),'scopes'=>['schema.bases:read']
        ]);

        Http::fake([
            'https://api.airtable.com/v0/meta/bases'=>Http::response([
                'bases'=>[['id'=>'appeiqT7XOsucMMOX','name'=>'GAN Wealth OS','permissionLevel'=>'create']]
            ],200),
        ]);

        $this->actingAs($u)->get('/galika/connections')
            ->assertOk()
            ->assertSee('GAN Wealth OS');

        $this->actingAs($u)->post('/galika/connections/airtable/base',[
            'base_id'=>'appeiqT7XOsucMMOX',
            'base_name'=>'GAN Wealth OS',
        ])->assertRedirect();

        $connection=\App\Models\GalikaConnection::where('user_id',$u->id)->where('provider','airtable')->firstOrFail();
        $this->assertSame('appeiqT7XOsucMMOX',data_get($connection->metadata,'base_id'));
        $this->assertSame('GAN Wealth OS',data_get($connection->metadata,'base_name'));
    }


    public function test_material_answer_is_saved_as_reusable_knowledge(): void
    {
        $u=User::factory()->create();
        $o=GalikaOpportunity::create(['canonical_key'=>'know','source'=>'test','employer'=>'Acme','title'=>'AI Engineer','url'=>'https://example.test','discovered_at'=>now()]);
        $a=GalikaApplication::create(['user_id'=>$u->id,'opportunity_id'=>$o->id,'application_key'=>'know-app','status'=>'BLOCKED_REQUIRES_USER']);
        $d=GalikaDecision::create(['application_id'=>$a->id,'decision_type'=>'MATERIAL_ANSWER','question'=>'Are you willing to relocate?','status'=>'OPEN']);

        $this->actingAs($u)->post("/galika/decisions/{$d->id}",['answer'=>'Yes, where appropriate'])->assertRedirect();

        $this->assertDatabaseHas('galika_answer_knowledge',[
            'user_id'=>$u->id,'intent'=>'RELOCATION','answer'=>'Yes, where appropriate'
        ]);
    }

    public function test_location_feasibility_blocks_restricted_remote_region_without_evidence(): void
    {
        $u=User::factory()->create();
        GalikaProfile::create(['user_id'=>$u->id,'timezone'=>'UTC','willing_to_relocate'=>false]);
        $r=app(\App\Galika\Services\LocationFeasibilityService::class)->evaluate($u->id,['location'=>'Remote - US only']);
        $this->assertFalse($r['feasible']);
        $this->assertSame('REMOTE_RESTRICTED',$r['reason']);
    }

    public function test_trust_engine_flags_scam_language(): void
    {
        $o=GalikaOpportunity::create([
            'canonical_key'=>'risk','source'=>'test','employer'=>'Unknown','title'=>'Executive Role',
            'url'=>'https://jobs.example.test/risk','description'=>'Pay a fee using crypto before interview',
            'source_authority'=>'AGGREGATOR','discovered_at'=>now()
        ]);
        $r=app(\App\Galika\Services\OpportunityTrustService::class)->assess($o);
        $this->assertSame('HIGH_RISK',$r['state']);
    }

    public function test_campaign_prefers_referral_when_relationship_exists(): void
    {
        $u=User::factory()->create();
        $employer=\App\Models\GalikaEmployer::create(['canonical_name'=>'Acme']);
        $person=\App\Models\GalikaPerson::create(['employer_id'=>$employer->id,'name'=>'Jane Doe','role'=>'Engineer']);
        \App\Models\GalikaRelationship::create(['user_id'=>$u->id,'person_id'=>$person->id,'strength'=>'STRONG']);
        $o=GalikaOpportunity::create([
            'canonical_key'=>'camp','source'=>'test','employer'=>'Acme','employer_id'=>$employer->id,
            'title'=>'AI Engineer','url'=>'https://example.test/jobs/1','discovered_at'=>now(),'match_score'=>90
        ]);
        $c=app(\App\Galika\Services\CampaignStrategyService::class)->plan($u->id,$o);
        $this->assertSame('REFERRAL_FIRST',$c->strategy);
    }

    public function test_follow_up_pauses_for_soft_bounce_and_terminal_inbound(): void
    {
        $u=User::factory()->create();
        $o=GalikaOpportunity::create(['canonical_key'=>'f','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://e.test','discovered_at'=>now()]);
        $a=GalikaApplication::create(['user_id'=>$u->id,'opportunity_id'=>$o->id,'application_key'=>'f1','status'=>'SUBMITTED_CONFIRMED','delivery_state'=>'SENT_PENDING']);
        $this->assertNull(app(\App\Galika\Services\CommunicationCadenceService::class)->next($a,0));
        $a->update(['delivery_state'=>'DELIVERED_NO_BOUNCE','inbound_state'=>'INTERVIEW']);
        $this->assertNull(app(\App\Galika\Services\CommunicationCadenceService::class)->next($a,0));
    }

    public function test_career_os_surfaces_are_user_accessible(): void
    {
        $u=User::factory()->create();
        foreach(['/galika/campaigns','/galika/relationships','/galika/personas','/galika/interviews','/galika/offers','/galika/events'] as $url){
            $this->actingAs($u)->get($url)->assertOk();
        }
    }


    public function test_source_metrics_recompute_yield(): void
    {
        $m=app(\App\Galika\Services\SourceMetricService::class);
        $m->bump('jobicy','discovered',10);
        $m->bump('jobicy','submitted',5);
        $m->bump('jobicy','response',2);
        $m->bump('jobicy','interview',1);
        $this->assertGreaterThan(0,\App\Models\GalikaSourceMetric::where('source','jobicy')->value('yield_score'));
    }

    public function test_campaign_materializes_executable_actions(): void
    {
        $u=User::factory()->create();
        $e=\App\Models\GalikaEmployer::create(['canonical_name'=>'Acme']);
        $p=\App\Models\GalikaPerson::create(['employer_id'=>$e->id,'name'=>'Jane','email'=>'jane@acme.test','relationship_type'=>'EMPLOYEE']);
        \App\Models\GalikaRelationship::create(['user_id'=>$u->id,'person_id'=>$p->id,'strength'=>'STRONG']);
        $o=GalikaOpportunity::create(['canonical_key'=>'exec','source'=>'test','employer'=>'Acme','employer_id'=>$e->id,'title'=>'AI Engineer','url'=>'https://e.test/job','discovered_at'=>now(),'match_score'=>90]);
        $c=app(\App\Galika\Services\CampaignStrategyService::class)->plan($u->id,$o);
        app(\App\Galika\Services\CampaignExecutionService::class)->materialize($c);
        $this->assertDatabaseHas('galika_campaign_actions',['campaign_id'=>$c->id,'action'=>'REQUEST_REFERRAL','state'=>'PENDING']);
    }

    public function test_human_assist_is_created_for_auth_blocker(): void
    {
        $u=User::factory()->create();
        $o=GalikaOpportunity::create(['canonical_key'=>'ha','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://e.test','discovered_at'=>now()]);
        $a=GalikaApplication::create(['user_id'=>$u->id,'opportunity_id'=>$o->id,'application_key'=>'ha1','status'=>'BLOCKED_REQUIRES_USER']);
        $h=app(\App\Galika\Services\HumanAssistService::class)->create($a,'AUTH','Re-authenticate',['url'=>'https://e.test']);
        $this->assertSame('OPEN',$h->state);
        $this->assertDatabaseHas('galika_human_assists',['application_id'=>$a->id,'kind'=>'AUTH','state'=>'OPEN']);
    }

    public function test_next_best_action_surfaces_application_blocker(): void
    {
        $u=User::factory()->create();
        $o=GalikaOpportunity::create(['canonical_key'=>'nba','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://e.test','discovered_at'=>now()]);
        $a=GalikaApplication::create(['user_id'=>$u->id,'opportunity_id'=>$o->id,'application_key'=>'nba1','status'=>'BLOCKED_REQUIRES_USER','blocker'=>'CAPTCHA']);
        app(\App\Galika\Services\NextBestActionService::class)->refreshUser($u->id);
        $this->assertDatabaseHas('galika_next_actions',['user_id'=>$u->id,'subject_type'=>'APPLICATION','subject_id'=>$a->id,'action'=>'RESOLVE_BLOCKER','priority'=>'HIGH']);
    }

    public function test_pipeline_canary_is_named_readiness_not_end_to_end(): void
    {
        $u=User::factory()->create();
        GalikaProfile::create(['user_id'=>$u->id,'timezone'=>'UTC','autonomous_apply_enabled'=>true]);
        $run=app(\App\Galika\Services\CanaryService::class)->run($u->id);
        $this->assertSame('PIPELINE_READINESS',$run->scenario);
    }

    public function test_persona_selector_prefers_matching_role(): void
    {
        $u=User::factory()->create();
        \App\Models\GalikaPersona::create(['user_id'=>$u->id,'name'=>'Data','target_roles'=>['Data Scientist'],'active'=>true,'performance_score'=>1]);
        $expected=\App\Models\GalikaPersona::create(['user_id'=>$u->id,'name'=>'AI','target_roles'=>['AI Engineer'],'active'=>true,'performance_score'=>1]);
        $o=new GalikaOpportunity(['title'=>'Senior AI Engineer']);
        $selected=app(\App\Galika\Services\PersonaSelectionService::class)->select($u->id,$o);
        $this->assertSame($expected->id,$selected->id);
    }

    public function test_privacy_service_blocks_sensitive_data_by_default(): void
    {
        $u=User::factory()->create();
        $svc=app(\App\Galika\Services\PrivacyDisclosureService::class);
        $this->assertFalse($svc->allowed($u->id,'passport','APPLICATION'));
        $this->assertTrue($svc->allowed($u->id,'email','APPLICATION'));
    }

    public function test_interview_and_offer_orchestration_create_next_actions(): void
    {
        $u=User::factory()->create();
        $o=GalikaOpportunity::create(['canonical_key'=>'io','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://e.test','discovered_at'=>now()]);
        $a=GalikaApplication::create(['user_id'=>$u->id,'opportunity_id'=>$o->id,'application_key'=>'io1','status'=>'SUBMITTED_CONFIRMED']);
        $i=\App\Models\GalikaInterview::create(['application_id'=>$a->id,'stage'=>'TECHNICAL','state'=>'INVITED']);
        app(\App\Galika\Services\InterviewOrchestrationService::class)->prepare($i);
        $offer=\App\Models\GalikaOffer::create(['application_id'=>$a->id,'state'=>'RECEIVED','currency'=>'USD']);
        app(\App\Galika\Services\OfferOrchestrationService::class)->analyze($offer);
        $this->assertDatabaseHas('galika_next_actions',['user_id'=>$u->id,'subject_type'=>'INTERVIEW','subject_id'=>$i->id]);
        $this->assertDatabaseHas('galika_next_actions',['user_id'=>$u->id,'subject_type'=>'OFFER','subject_id'=>$offer->id]);
    }


    public function test_authoritative_reverification_closes_dead_role(): void
    {
        Http::fake(['https://jobs.example.test/closed'=>Http::response('This job is no longer available',200)]);
        $o=GalikaOpportunity::create(['canonical_key'=>'dead','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://jobs.example.test/closed','discovered_at'=>now()]);
        $r=app(\App\Galika\Services\AuthoritativeReverificationService::class)->verify($o);
        $this->assertFalse($r['open']);
        $this->assertFalse((bool)$o->fresh()->official_open);
    }

    public function test_deep_privacy_filters_nested_sensitive_fields(): void
    {
        $u=User::factory()->create();
        $out=app(\App\Galika\Services\DeepPrivacyDisclosureService::class)->filter($u->id,[
            'profile'=>['email'=>'x@example.com','passport'=>'SECRET','home_address'=>'Hidden'],
            'evidence'=>[['fact'=>'Python','medical'=>'PRIVATE']]
        ],'APPLICATION');
        $this->assertSame('x@example.com',$out['profile']['email']);
        $this->assertArrayNotHasKey('passport',$out['profile']);
        $this->assertArrayNotHasKey('home_address',$out['profile']);
        $this->assertArrayNotHasKey('medical',$out['evidence'][0]);
    }

    public function test_capacity_planner_blocks_when_weekly_interview_limit_reached(): void
    {
        $u=User::factory()->create();
        GalikaProfile::create(['user_id'=>$u->id,'timezone'=>'UTC','max_interviews_per_week'=>1]);
        $o=GalikaOpportunity::create(['canonical_key'=>'cap','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://e.test','discovered_at'=>now()]);
        $a=GalikaApplication::create(['user_id'=>$u->id,'opportunity_id'=>$o->id,'application_key'=>'cap1','status'=>'SUBMITTED_CONFIRMED']);
        \App\Models\GalikaInterview::create(['application_id'=>$a->id,'stage'=>'INTERVIEW','state'=>'SCHEDULED','starts_at'=>now()->addDay()]);
        $r=app(\App\Galika\Services\CapacityPlannerService::class)->canPursue($u->id);
        $this->assertFalse($r['ok']);
        $this->assertSame('INTERVIEW_CAPACITY_REACHED',$r['reason']);
    }

    public function test_queue_orchestrator_enqueues_idempotent_apply_items(): void
    {
        $u=User::factory()->create();
        GalikaProfile::create(['user_id'=>$u->id,'timezone'=>'UTC','autonomous_apply_enabled'=>true]);
        GalikaOpportunity::create(['canonical_key'=>'q1','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://e.test/1','discovered_at'=>now()]);
        $svc=app(\App\Galika\Services\QueueOrchestratorService::class);
        $svc->enqueueApplications(10);$svc->enqueueApplications(10);
        $this->assertSame(1,\DB::table('galika_execution_work_items')->where('kind','APPLY')->count());
    }

    public function test_marketplace_finance_schema_supports_contract_milestone_payment(): void
    {
        $employer=User::factory()->create();$freelancer=User::factory()->create();
        $category=\App\Models\JobCategory::create(['category_name'=>'Engineering']);
        $job=\App\Models\Job::create(['headline'=>'Build AI','title'=>'Build AI','job_id'=>'JOB-'.uniqid(),'category_id'=>$category->id,'user_id'=>$employer->id,'description'=>'AI build','skills'=>'PHP,AI','payment_category'=>'fixed','pay_rate'=>'1000','status'=>'active']);
        $contract=\App\Models\Contract::create(['job_id'=>$job->id,'employer_id'=>$employer->id,'freelancer_id'=>$freelancer->id,'state'=>'ACTIVE','value'=>1000,'currency'=>'USD']);
        $milestone=\App\Models\Milestone::create(['contract_id'=>$contract->id,'title'=>'Delivery','amount'=>1000]);
        \App\Models\Payment::create(['contract_id'=>$contract->id,'milestone_id'=>$milestone->id,'payer_id'=>$employer->id,'payee_id'=>$freelancer->id,'amount'=>1000,'currency'=>'USD','state'=>'PENDING']);
        $this->assertDatabaseHas('payments',['contract_id'=>$contract->id,'amount'=>1000,'state'=>'PENDING']);
    }

    public function test_pages_workflow_and_status_surface_exist(): void
    {
        $this->assertFileExists(base_path('.github/workflows/pages.yml'));
        $this->assertFileExists(public_path('status/index.html'));
    }

    public function test_backup_health_snapshot_returns_database_metadata(): void
    {
        $r=app(\App\Galika\Services\BackupHealthService::class)->snapshot();
        $this->assertArrayHasKey('database',$r);
        $this->assertArrayHasKey('tables',$r);
    }


    public function test_greenhouse_and_lever_direct_discovery_are_supported(): void
    {
        config(['galika.discovery.greenhouse_boards'=>['acme'],'galika.discovery.lever_sites'=>['acme'],'galika.discovery.queries'=>[]]);
        Http::fake([
            'https://boards-api.greenhouse.io/v1/boards/acme/jobs*'=>Http::response(['jobs'=>[[
                'id'=>123,'title'=>'AI Engineer','absolute_url'=>'https://boards.greenhouse.io/acme/jobs/123',
                'location'=>['name'=>'Remote'],'content'=>'Build AI','updated_at'=>now()->toIso8601String()
            ]]],200),
            'https://api.lever.co/v0/postings/acme*'=>Http::response([[
                'id'=>'lev1','text'=>'Data Scientist','hostedUrl'=>'https://jobs.lever.co/acme/lev1',
                'categories'=>['location'=>'Remote'],'descriptionPlain'=>'Data work'
            ]],200),
        ]);
        $count=app(\App\Galika\Services\SourceBrokerService::class)->discover();
        $this->assertGreaterThanOrEqual(2,$count);
        $this->assertDatabaseHas('galika_opportunities',['source'=>'greenhouse','requisition_id'=>'123']);
        $this->assertDatabaseHas('galika_opportunities',['source'=>'lever','requisition_id'=>'lev1']);
    }

    public function test_role_lineage_records_versions(): void
    {
        $o=GalikaOpportunity::create(['canonical_key'=>'lin','source'=>'test','employer'=>'Acme','title'=>'AI Engineer','url'=>'https://e.test','discovered_at'=>now()]);
        $svc=app(\App\Galika\Services\RoleLineageService::class);
        $svc->observe($o,['title'=>'AI Engineer','location'=>'Remote','description'=>'v1']);
        $svc->observe($o,['title'=>'AI Engineer','location'=>'Remote','description'=>'v2']);
        $this->assertSame(2,\App\Models\GalikaRoleLineage::where('opportunity_id',$o->id)->count());
    }

    public function test_human_assist_can_resume_with_token(): void
    {
        $u=User::factory()->create();
        $o=GalikaOpportunity::create(['canonical_key'=>'assist','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://e.test','discovered_at'=>now()]);
        $a=GalikaApplication::create(['user_id'=>$u->id,'opportunity_id'=>$o->id,'application_key'=>'assist1','status'=>'BLOCKED_REQUIRES_USER','blocker'=>'AUTH']);
        $assist=app(\App\Galika\Services\HumanAssistService::class)->create($a,'AUTH','Login');
        $this->assertNotNull($assist->resume_token);
        $this->actingAs($u)->get('/galika/assist/'.$assist->resume_token.'/resume')->assertRedirect(route('galika.applications'));
        $this->assertSame('RESUMED',$assist->fresh()->state);
        $this->assertSame('VERIFIED',$a->fresh()->status);
    }

    public function test_application_withdrawal_records_external_truth_event(): void
    {
        $u=User::factory()->create();
        $o=GalikaOpportunity::create(['canonical_key'=>'w','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://e.test','discovered_at'=>now()]);
        $a=GalikaApplication::create(['user_id'=>$u->id,'opportunity_id'=>$o->id,'application_key'=>'w1','status'=>'SUBMITTED_CONFIRMED']);
        $this->actingAs($u)->post('/galika/applications/'.$a->id.'/withdraw',['reason'=>'Changed direction'])->assertRedirect();
        $this->assertSame('WITHDRAWN',$a->fresh()->correction_state);
        $this->assertDatabaseHas('galika_career_events',['user_id'=>$u->id,'subject_type'=>'APPLICATION','subject_id'=>$a->id,'event_type'=>'WITHDRAWN']);
    }

    public function test_durable_worker_supports_non_application_kinds(): void
    {
        $u=User::factory()->create();
        $o=GalikaOpportunity::create(['canonical_key'=>'dw','source'=>'test','employer'=>'Acme','title'=>'AI','url'=>'https://e.test','discovered_at'=>now()]);
        $a=GalikaApplication::create(['user_id'=>$u->id,'opportunity_id'=>$o->id,'application_key'=>'dw1','status'=>'SUBMITTED_CONFIRMED']);
        $i=\App\Models\GalikaInterview::create(['application_id'=>$a->id,'stage'=>'INTERVIEW','state'=>'INVITED']);
        app(\App\Galika\Services\WorkQueueService::class)->enqueue('INTERVIEW_PREP',['interview_id'=>$i->id],'ip-'.$i->id);
        $this->artisan('galika:worker --limit=10')->assertExitCode(0);
        $this->assertSame('READY',data_get($i->fresh()->prep_plan,'status'));
    }
}
