<?php
namespace App\Http\Controllers;

use App\Galika\Services\CanaryService;
use App\Galika\Services\AirtableAdapter;
use App\Galika\Services\ConnectionHealthService;
use App\Galika\Services\ConnectionVault;
use App\Galika\Services\CvIngestionService;
use App\Galika\Services\OAuthService;
use App\Models\GalikaApplication;
use App\Models\GalikaConnection;
use App\Models\GalikaDecision;
use App\Models\GalikaDocument;
use App\Models\GalikaEvidence;
use App\Models\GalikaIntegration;
use App\Models\GalikaOpportunity;
use App\Models\GalikaProfile;
use App\Models\GalikaWealthItem;
use Illuminate\Http\Request;

class GalikaController extends Controller
{
    public function __construct(){ $this->middleware('auth'); }

    public function dashboard(Request $r){
        $uid=$r->user()->id;
        return view('galika.dashboard',[
            'profile'=>GalikaProfile::firstOrCreate(['user_id'=>$uid],['timezone'=>config('app.timezone')]),
            'opportunities'=>GalikaOpportunity::orderByDesc('discovered_at')->limit(20)->get(),
            'applications'=>GalikaApplication::where('user_id',$uid)->with('opportunity')->latest()->limit(20)->get(),
            'decisions'=>GalikaDecision::whereHas('application',fn($q)=>$q->where('user_id',$uid))->where('status','OPEN')->get(),
            'integrations'=>GalikaConnection::where('user_id',$uid)->get(),
            'wealth'=>GalikaWealthItem::where('user_id',$uid)->orderBy('priority')->limit(10)->get(),
        ]);
    }

    public function profile(Request $r){
        $uid=$r->user()->id;
        return view('galika.profile',[
            'profile'=>GalikaProfile::firstOrCreate(['user_id'=>$uid],['timezone'=>config('app.timezone')]),
            'evidence'=>GalikaEvidence::where('user_id',$uid)->orderByDesc('verified')->get(),
            'documents'=>GalikaDocument::where('user_id',$uid)->latest()->get(),
        ]);
    }

    public function saveProfile(Request $r){
        $data=$r->validate([
            'headline'=>'nullable|string|max:255','location'=>'nullable|string|max:255','country'=>'nullable|string|max:100',
            'timezone'=>'required|string|max:100','minimum_match_score'=>'required|integer|min:0|max:100',
            'autonomous_apply_enabled'=>'sometimes|boolean','pause_all_execution'=>'sometimes|boolean',
            'review_mode'=>'required|in:MATERIAL_ONLY,REVIEW_ALL,AUTONOMOUS'
        ]);
        $data['autonomous_apply_enabled']=$r->boolean('autonomous_apply_enabled');
        $data['pause_all_execution']=$r->boolean('pause_all_execution');
        GalikaProfile::updateOrCreate(['user_id'=>$r->user()->id],$data);
        return back()->with('success','GALIKA profile updated.');
    }

    public function uploadCv(Request $r,CvIngestionService $cv){
        $data=$r->validate(['cv'=>'required|file|max:10240|mimes:pdf,docx,txt']);
        $doc=$cv->ingest($r->user()->id,$data['cv']);
        return redirect()->route('galika.documents.review',$doc);
    }

    public function reviewDocument(Request $r,GalikaDocument $document){
        abort_unless($document->user_id===$r->user()->id,403);
        return view('galika.document-review',compact('document'));
    }

    public function confirmDocument(Request $r,GalikaDocument $document,CvIngestionService $cv){
        abort_unless($document->user_id===$r->user()->id,403);
        $accepted=array_map('intval',$r->input('accepted',[]));
        $cv->confirm($document,$accepted);
        return redirect()->route('galika.profile')->with('success','Verified CV evidence added to your GALIKA profile.');
    }

    public function connections(Request $r,AirtableAdapter $airtable){
        $connections=GalikaConnection::where('user_id',$r->user()->id)->get();
        $bases=[];$airtableError=null;
        if($connections->firstWhere('provider','airtable')){
            try{$bases=$airtable->bases($r->user()->id);}catch(\Throwable $e){$airtableError=$e->getMessage();}
        }
        return view('galika.connections',compact('connections','bases','airtableError'));
    }

    public function selectAirtableBase(Request $r,AirtableAdapter $airtable){
        $data=$r->validate(['base_id'=>'required|string|regex:/^app[A-Za-z0-9]{14}$/','base_name'=>'required|string|max:255']);
        $airtable->selectBase($r->user()->id,$data['base_id'],$data['base_name']);
        return back()->with('success','Airtable base selected.');
    }

    public function saveApiConnection(Request $r,ConnectionVault $vault){
        $data=$r->validate(['provider'=>'required|in:openai,airtable,tinyfish','api_key'=>'required|string|min:8']);
        $vault->store($r->user()->id,$data['provider'],'api_key',['api_key'=>$data['api_key']]);
        return back()->with('success',ucfirst($data['provider']).' credential saved securely.');
    }

    public function testConnection(Request $r,string $provider,ConnectionHealthService $health){
        $result=$health->test($r->user()->id,$provider);
        return back()->with($result['ok']?'success':'error',$result['ok']?"{$provider} connection is healthy":"{$provider} connection failed: ".($result['error']??$result['status']??'unknown'));
    }

    public function oauthStart(Request $r,string $provider,OAuthService $oauth){
        abort_unless(in_array($provider,['gmail','airtable','linkedin','lever'],true),404);
        return redirect()->away($oauth->authorizationUrl($r->user()->id,$provider));
    }

    public function oauthCallback(Request $r,string $provider,OAuthService $oauth){
        $r->validate(['code'=>'required|string','state'=>'required|string']);
        $oauth->exchange($r->user()->id,$provider,(string)$r->string('code'),(string)$r->string('state'));
        return redirect()->route('galika.connections')->with('success',ucfirst($provider).' connected.');
    }

    public function opportunities(){return view('galika.opportunities',['opportunities'=>GalikaOpportunity::orderByDesc('discovered_at')->paginate(30)]);}
    public function applications(Request $r){return view('galika.applications',['applications'=>GalikaApplication::where('user_id',$r->user()->id)->with('opportunity')->orderByDesc('created_at')->paginate(30)]);}
    public function decisions(Request $r){return view('galika.decisions',['decisions'=>GalikaDecision::whereHas('application',fn($q)=>$q->where('user_id',$r->user()->id))->with('application.opportunity')->orderByDesc('created_at')->paginate(30)]);}
    public function resolveDecision(Request $r,GalikaDecision $decision){abort_unless($decision->application?->user_id===$r->user()->id,403);$data=$r->validate(['answer'=>'required|string|max:5000']);$decision->update(['answer'=>$data['answer'],'status'=>'RESOLVED','resolved_at'=>now()]);$decision->application->update(['status'=>'VERIFIED','blocker'=>null,'failure_class'=>null]);return back()->with('success','Decision resolved.');}
    public function analytics(Request $r){$uid=$r->user()->id;$q=GalikaApplication::where('user_id',$uid);$total=(clone $q)->count();$submitted=(clone $q)->where('status','SUBMITTED_CONFIRMED')->count();$interviews=(clone $q)->where('inbound_state','INTERVIEW')->count();$offers=(clone $q)->where('inbound_state','OFFER')->count();$avg=(clone $q)->whereNotNull('discovery_to_submit_sec')->avg('discovery_to_submit_sec');return view('galika.analytics',compact('total','submitted','interviews','offers','avg'));}

    public function wealth(Request $r){return view('galika.wealth',['items'=>GalikaWealthItem::where('user_id',$r->user()->id)->orderBy('priority')->paginate(30)]);}
    public function createWealth(Request $r){
        $data=$r->validate(['lane'=>'required|in:CONSULTING,B2B,TENDER,PARTNERSHIP,PRODUCT,IP,OTHER','title'=>'required|string|max:255','counterparty'=>'nullable|string|max:255','source_url'=>'nullable|url','estimated_value'=>'nullable|numeric|min:0','currency'=>'nullable|string|size:3']);
        GalikaWealthItem::create($data+['user_id'=>$r->user()->id,'state'=>'DISCOVERED']);
        return back()->with('success','Wealth opportunity queued for GALIKA.');
    }

    public function canary(Request $r,CanaryService $canary){
        $run=$canary->run($r->user()->id);
        return back()->with($run->state==='PASSED'?'success':'error','Canary '.$run->state.' — '.$run->run_key);
    }
}
