<?php
namespace Tests\Feature;

use App\Galika\Services\BackupHealthService;
use App\Galika\Services\EconomicTruthService;
use App\Galika\Services\OutboxService;
use App\Models\GalikaCustomer;
use App\Models\GalikaInvoice;
use App\Models\GalikaProfile;
use App\Models\GalikaScorecard;
use App\Models\GalikaSubscription;
use App\Models\GalikaWealthItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GalikaEconomicResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_and_confirmed_mpesa_payment_create_economic_truth(): void
    {
        $user=User::factory()->create();
        $item=GalikaWealthItem::create([
            'user_id'=>$user->id,'lane'=>'CONSULTING','title'=>'Operations Intelligence Diagnostic',
            'counterparty'=>'buyer@example.com','state'=>'QUALIFIED','estimated_value'=>95000,'currency'=>'KES'
        ]);

        $truth=app(EconomicTruthService::class);
        $invoice=$truth->issueInvoice($item,[['description'=>'Diagnostic','quantity'=>1,'unit_price'=>95000]]);
        $this->assertSame('ISSUED',$invoice->state);
        $this->assertSame(95000.0,(float)$invoice->total);

        $tx=$truth->recordPayment(
            $user->id,$invoice,'MPESA','mpesa-checkout-1',95000,'KES','QAA123456',
            ['ResultCode'=>0],'CONFIRMED'
        );

        $this->assertSame('CONFIRMED',$tx->state);
        $this->assertSame('PAID',$invoice->fresh()->state);
        $this->assertSame(95000.0,(float)$invoice->fresh()->amount_paid);
        $this->assertSame('CUSTOMER',$invoice->customer->fresh()->state);
        $this->assertSame('WON',$item->fresh()->state);
    }

    public function test_payment_idempotency_prevents_double_counting(): void
    {
        $user=User::factory()->create();
        $item=GalikaWealthItem::create([
            'user_id'=>$user->id,'lane'=>'CONSULTING','title'=>'Audit','counterparty'=>'buyer@example.com',
            'state'=>'QUALIFIED','currency'=>'KES'
        ]);
        $truth=app(EconomicTruthService::class);
        $invoice=$truth->issueInvoice($item,[['description'=>'Audit','quantity'=>1,'unit_price'=>1000]]);

        $a=$truth->recordPayment($user->id,$invoice,'MPESA','same-key',1000,'KES','R1',[],'CONFIRMED');
        $b=$truth->recordPayment($user->id,$invoice,'MPESA','same-key',1000,'KES','R1',[],'CONFIRMED');

        $this->assertSame($a->id,$b->id);
        $this->assertSame(1,DB::table('galika_payment_transactions')->where('idempotency_key','same-key')->count());
        $this->assertSame(1000.0,(float)$invoice->fresh()->amount_paid);
    }

    public function test_mrr_normalizes_subscription_intervals(): void
    {
        $user=User::factory()->create();
        $customer=GalikaCustomer::create(['user_id'=>$user->id,'name'=>'Acme','state'=>'CUSTOMER','currency'=>'KES']);
        GalikaSubscription::create([
            'user_id'=>$user->id,'customer_id'=>$customer->id,'name'=>'Monthly','currency'=>'KES',
            'recurring_amount'=>12000,'interval'=>'MONTHLY','state'=>'ACTIVE','started_at'=>now()
        ]);
        GalikaSubscription::create([
            'user_id'=>$user->id,'customer_id'=>$customer->id,'name'=>'Annual','currency'=>'KES',
            'recurring_amount'=>120000,'interval'=>'ANNUAL','state'=>'ACTIVE','started_at'=>now()
        ]);

        $this->assertSame(22000.0,app(EconomicTruthService::class)->mrr($user->id,'KES'));
    }

    public function test_scorecard_counts_only_canonical_invoiced_and_collected_money(): void
    {
        $user=User::factory()->create();
        GalikaProfile::create(['user_id'=>$user->id,'timezone'=>'UTC']);
        $item=GalikaWealthItem::create([
            'user_id'=>$user->id,'lane'=>'CONSULTING','title'=>'Diagnostic','counterparty'=>'buyer@example.com',
            'state'=>'QUALIFIED','estimated_value'=>999999,'currency'=>'KES'
        ]);

        $truth=app(EconomicTruthService::class);
        $invoice=$truth->issueInvoice($item,[['description'=>'Diagnostic','quantity'=>1,'unit_price'=>95000]]);
        $truth->recordPayment($user->id,$invoice,'BANK','bank-1',50000,'KES','BANKREF',[],'CONFIRMED');

        $score=$truth->buildScorecard($user->id,now()->subDay(),now()->addDay(),'KES');

        $this->assertSame(95000.0,(float)$score->revenue_invoiced);
        $this->assertSame(50000.0,(float)$score->cash_collected);
        $this->assertNotSame(999999.0,(float)$score->revenue_invoiced);
        $this->assertDatabaseHas('galika_scorecards',['id'=>$score->id,'cash_collected'=>50000]);
    }

    public function test_outbox_moves_to_dead_letter_and_can_be_replayed(): void
    {
        DB::table('galika_outbox')->insert([
            'event_id'=>'evt-1','user_id'=>null,'destination'=>'airtable','kind'=>'TEST',
            'payload'=>json_encode(['x'=>1]),'status'=>'RETRY','attempts'=>8,
            'available_at'=>now(),'leased_until'=>null,'last_error'=>'broken',
            'created_at'=>now(),'updated_at'=>now()
        ]);
        $id=(int)DB::table('galika_outbox')->value('id');

        $outbox=app(OutboxService::class);
        $outbox->retry($id,'still broken',8,8);

        $this->assertDatabaseHas('galika_outbox',['id'=>$id,'status'=>'DEAD']);
        $dead=DB::table('galika_outbox_dead_letters')->where('outbox_id',$id)->first();
        $this->assertNotNull($dead);

        $outbox->replayDeadLetter($dead->id);
        $this->assertDatabaseHas('galika_outbox',['id'=>$id,'status'=>'RETRY','attempts'=>0]);
        $this->assertDatabaseHas('galika_outbox_dead_letters',['id'=>$dead->id,'state'=>'REPLAYED']);
    }

    public function test_backup_check_persists_schema_health(): void
    {
        $result=app(BackupHealthService::class)->snapshot();
        $this->assertTrue($result['ok']);
        $this->assertDatabaseHas('galika_backup_checks',['kind'=>'SCHEMA_HEALTH','state'=>'PASSED']);
    }
}
