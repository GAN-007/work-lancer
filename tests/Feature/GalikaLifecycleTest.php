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
}
