<?php
namespace App\Http\Controllers;

use App\Models\GalikaApplication;
use App\Models\GalikaDecision;
use App\Models\GalikaEvidence;
use App\Models\GalikaIntegration;
use App\Models\GalikaOpportunity;
use App\Models\GalikaProfile;
use Illuminate\Http\Request;

class GalikaController extends Controller{
    public function __construct(){ $this->middleware('auth'); }
    public function dashboard(Request $r){$uid=$r->user()->id;return view('galika.dashboard',['profile'=>GalikaProfile::firstOrCreate(['user_id'=>$uid],['timezone'=>config('app.timezone')]),'opportunities'=>GalikaOpportunity::orderByDesc('discovered_at')->limit(20)->get(),'applications'=>GalikaApplication::where('user_id',$uid)->with('opportunity')->latest()->limit(20)->get(),'decisions'=>GalikaDecision::whereHas('application',fn($q)=>$q->where('user_id',$uid))->where('status','OPEN')->get(),'integrations'=>GalikaIntegration::where('user_id',$uid)->get()]);}
    public function profile(Request $r){$uid=$r->user()->id;return view('galika.profile',['profile'=>GalikaProfile::firstOrCreate(['user_id'=>$uid],['timezone'=>config('app.timezone')]),'evidence'=>GalikaEvidence::where('user_id',$uid)->orderByDesc('verified')->get()]);}
    public function saveProfile(Request $r){$data=$r->validate(['headline'=>'nullable|string|max:255','location'=>'nullable|string|max:255','country'=>'nullable|string|max:100','timezone'=>'required|string|max:100','minimum_match_score'=>'required|integer|min:0|max:100','autonomous_apply_enabled'=>'sometimes|boolean','pause_all_execution'=>'sometimes|boolean','review_mode'=>'required|in:MATERIAL_ONLY,REVIEW_ALL,AUTONOMOUS']);$data['autonomous_apply_enabled']=$r->boolean('autonomous_apply_enabled');$data['pause_all_execution']=$r->boolean('pause_all_execution');GalikaProfile::updateOrCreate(['user_id'=>$r->user()->id],$data);return back()->with('success','GALIKA profile updated.');}
    public function opportunities(){return view('galika.opportunities',['opportunities'=>GalikaOpportunity::orderByDesc('discovered_at')->paginate(30)]);}
    public function applications(Request $r){return view('galika.applications',['applications'=>GalikaApplication::where('user_id',$r->user()->id)->with('opportunity')->orderByDesc('created_at')->paginate(30)]);}
    public function decisions(Request $r){return view('galika.decisions',['decisions'=>GalikaDecision::whereHas('application',fn($q)=>$q->where('user_id',$r->user()->id))->with('application.opportunity')->orderByDesc('created_at')->paginate(30)]);}
    public function resolveDecision(Request $r,GalikaDecision $decision){abort_unless($decision->application?->user_id===$r->user()->id,403);$data=$r->validate(['answer'=>'required|string|max:5000']);$decision->update(['answer'=>$data['answer'],'status'=>'RESOLVED','resolved_at'=>now()]);$decision->application->update(['status'=>'VERIFIED','blocker'=>null,'failure_class'=>null]);return back()->with('success','Decision resolved.');}
    public function analytics(Request $r){$uid=$r->user()->id;$q=GalikaApplication::where('user_id',$uid);$total=(clone $q)->count();$submitted=(clone $q)->where('status','SUBMITTED_CONFIRMED')->count();$interviews=(clone $q)->where('inbound_state','INTERVIEW')->count();$offers=(clone $q)->where('inbound_state','OFFER')->count();$avg=(clone $q)->whereNotNull('discovery_to_submit_sec')->avg('discovery_to_submit_sec');return view('galika.analytics',compact('total','submitted','interviews','offers','avg'));}
}
