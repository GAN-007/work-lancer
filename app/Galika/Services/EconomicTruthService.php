<?php
namespace App\Galika\Services;

use App\Models\GalikaCustomer;
use App\Models\GalikaInvoice;
use App\Models\GalikaPaymentTransaction;
use App\Models\GalikaScorecard;
use App\Models\GalikaSubscription;
use App\Models\GalikaWealthItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EconomicTruthService
{
    public function customerForWealthItem(GalikaWealthItem $item): GalikaCustomer
    {
        $email=filter_var($item->counterparty,FILTER_VALIDATE_EMAIL)?mb_strtolower($item->counterparty):null;
        $name=$email?Str::before($email,'@'):($item->counterparty?:$item->title);

        $customer=GalikaCustomer::firstOrCreate(
            ['user_id'=>$item->user_id,'email'=>$email],
            ['name'=>$name,'company'=>$email?null:$item->counterparty,'state'=>'PROSPECT','currency'=>$item->currency?:'KES']
        );

        return $customer;
    }

    public function issueInvoice(GalikaWealthItem $item,array $lines,?\DateTimeInterface $dueAt=null): GalikaInvoice
    {
        if(!$lines) throw new \InvalidArgumentException('Invoice requires at least one line item.');

        $customer=$this->customerForWealthItem($item);
        $subtotal=0.0;
        $normalized=[];

        foreach($lines as $line){
            $description=trim((string)($line['description']??''));
            $quantity=(float)($line['quantity']??1);
            $unit=(float)($line['unit_price']??0);
            if($description===''||$quantity<=0||$unit<0) throw new \InvalidArgumentException('Invalid invoice line.');
            $amount=round($quantity*$unit,2);
            $subtotal+=$amount;
            $normalized[]=['description'=>$description,'quantity'=>$quantity,'unit_price'=>$unit,'amount'=>$amount];
        }

        $tax=0.0;
        $total=round($subtotal+$tax,2);

        return DB::transaction(function() use($item,$customer,$normalized,$subtotal,$tax,$total,$dueAt){
            $invoice=GalikaInvoice::create([
                'user_id'=>$item->user_id,
                'customer_id'=>$customer->id,
                'wealth_item_id'=>$item->id,
                'invoice_number'=>$this->nextInvoiceNumber($item->user_id),
                'currency'=>$item->currency?:'KES',
                'subtotal'=>$subtotal,
                'tax_amount'=>$tax,
                'total'=>$total,
                'amount_paid'=>0,
                'state'=>'ISSUED',
                'issued_at'=>now(),
                'due_at'=>$dueAt?:now()->addDays(7),
                'lines'=>$normalized,
                'metadata'=>['source'=>'GALIKA_WEALTH_ITEM'],
            ]);
            if(in_array($item->state,['QUALIFIED','EXECUTING'],true)) $item->update(['state'=>'PROPOSAL_OR_INVOICE_SENT']);
            return $invoice;
        });
    }

    public function recordPayment(
        int $userId,
        ?GalikaInvoice $invoice,
        string $provider,
        string $idempotencyKey,
        float $amount,
        string $currency,
        ?string $providerReference,
        array $providerPayload=[],
        string $state='CONFIRMED'
    ): GalikaPaymentTransaction {
        if($amount<=0) throw new \InvalidArgumentException('Payment amount must be positive.');
        $provider=strtoupper(trim($provider));
        if(!in_array($provider,['MPESA','BANK','VISA','CARD','PAYPAL'],true)) throw new \InvalidArgumentException('Unsupported payment provider.');

        return DB::transaction(function() use($userId,$invoice,$provider,$idempotencyKey,$amount,$currency,$providerReference,$providerPayload,$state){
            $existing=GalikaPaymentTransaction::where('idempotency_key',$idempotencyKey)->first();
            if($existing) return $existing;

            $tx=GalikaPaymentTransaction::create([
                'user_id'=>$userId,
                'invoice_id'=>$invoice?->id,
                'customer_id'=>$invoice?->customer_id,
                'provider'=>$provider,
                'provider_reference'=>$providerReference,
                'idempotency_key'=>$idempotencyKey,
                'currency'=>strtoupper($currency),
                'amount'=>$amount,
                'state'=>$state,
                'initiated_at'=>now(),
                'confirmed_at'=>$state==='CONFIRMED'?now():null,
                'failed_at'=>$state==='FAILED'?now():null,
                'provider_payload'=>$providerPayload,
            ]);

            if($state==='CONFIRMED'&&$invoice){
                $confirmed=(float)GalikaPaymentTransaction::where('invoice_id',$invoice->id)->where('state','CONFIRMED')->sum('amount');
                $newState=$confirmed+0.00001 >= (float)$invoice->total?'PAID':'PARTIALLY_PAID';
                $invoice->update([
                    'amount_paid'=>$confirmed,
                    'state'=>$newState,
                    'paid_at'=>$newState==='PAID'?now():null,
                ]);
                if($newState==='PAID'){
                    $invoice->customer?->update(['state'=>'CUSTOMER','won_at'=>$invoice->customer?->won_at?:now()]);
                    if($invoice->wealthItem) $invoice->wealthItem->update(['state'=>'WON']);
                }
            }

            return $tx;
        });
    }

    public function mrr(int $userId,?string $currency=null): float
    {
        $q=GalikaSubscription::where('user_id',$userId)->where('state','ACTIVE');
        if($currency)$q->where('currency',strtoupper($currency));
        $sum=0.0;
        foreach($q->get() as $subscription){
            $amount=(float)$subscription->recurring_amount;
            $sum+=match($subscription->interval){
                'WEEKLY'=>$amount*52/12,
                'QUARTERLY'=>$amount/3,
                'ANNUAL'=>$amount/12,
                default=>$amount,
            };
        }
        return round($sum,2);
    }

    public function buildScorecard(int $userId,\DateTimeInterface $from,\DateTimeInterface $to,string $currency='KES'): GalikaScorecard
    {
        $start=\Carbon\Carbon::instance(\DateTime::createFromInterface($from))->startOfDay();
        $end=\Carbon\Carbon::instance(\DateTime::createFromInterface($to))->endOfDay();

        $revenue=(float)GalikaInvoice::where('user_id',$userId)->whereBetween('issued_at',[$start,$end])->whereIn('state',['ISSUED','PARTIALLY_PAID','PAID'])->where('currency',$currency)->sum('total');
        $cash=(float)GalikaPaymentTransaction::where('user_id',$userId)->where('state','CONFIRMED')->where('currency',$currency)->whereBetween('confirmed_at',[$start,$end])->sum('amount');

        $qualified=(int)GalikaWealthItem::where('user_id',$userId)->whereBetween('updated_at',[$start,$end])->whereIn('state',['QUALIFIED','EXECUTING','PROPOSAL_OR_INVOICE_SENT','WON'])->count();
        $conversations=DB::table('galika_conversations')->where('user_id',$userId)->whereBetween('occurred_at',[$start,$end]);
        $outreach=(clone $conversations)->where('direction','OUTBOUND')->where('classification','WEALTH_OUTREACH')->count();
        $followups=(clone $conversations)->where('direction','OUTBOUND')->where('classification','FOLLOW_UP')->count();
        $replies=(clone $conversations)->where('direction','INBOUND')->whereIn('classification',['RECRUITER_REPLY','INFO_REQUEST','INTERVIEW','OFFER'])->count();
        $meetings=(int)DB::table('galika_interviews')->join('galika_applications','galika_interviews.application_id','=','galika_applications.id')->where('galika_applications.user_id',$userId)->whereBetween('galika_interviews.created_at',[$start,$end])->count();
        $proposals=(int)GalikaInvoice::where('user_id',$userId)->whereBetween('issued_at',[$start,$end])->count();
        $wins=(int)GalikaInvoice::where('user_id',$userId)->where('state','PAID')->whereBetween('paid_at',[$start,$end])->count();
        $customers=(int)GalikaCustomer::where('user_id',$userId)->where('state','CUSTOMER')->whereBetween('won_at',[$start,$end])->count();

        return GalikaScorecard::updateOrCreate(
            ['user_id'=>$userId,'period_start'=>$start->toDateString(),'period_end'=>$end->toDateString()],
            [
                'revenue_invoiced'=>$revenue,
                'cash_collected'=>$cash,
                'mrr'=>$this->mrr($userId,$currency),
                'qualified_leads'=>$qualified,
                'outreach_sent'=>$outreach,
                'followups_sent'=>$followups,
                'substantive_replies'=>$replies,
                'meetings'=>$meetings,
                'proposals'=>$proposals,
                'wins'=>$wins,
                'customers'=>$customers,
                'sellable_assets_shipped'=>0,
                'evidence'=>[
                    'currency'=>$currency,
                    'invoice_ids'=>GalikaInvoice::where('user_id',$userId)->whereBetween('issued_at',[$start,$end])->pluck('id')->all(),
                    'payment_ids'=>GalikaPaymentTransaction::where('user_id',$userId)->where('state','CONFIRMED')->whereBetween('confirmed_at',[$start,$end])->pluck('id')->all(),
                ],
                'notes'=>'Generated from canonical PostgreSQL evidence. Pipeline estimates are excluded from revenue and cash.',
            ]
        );
    }

    private function nextInvoiceNumber(int $userId): string
    {
        $prefix='GAN-'.now()->format('Ym').'-';
        $count=GalikaInvoice::where('user_id',$userId)->where('invoice_number','like',$prefix.'%')->lockForUpdate()->count()+1;
        return $prefix.str_pad((string)$count,5,'0',STR_PAD_LEFT);
    }
}
