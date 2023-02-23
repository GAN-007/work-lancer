<?php

namespace App\Http\Controllers\Freelancer;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use App\Models\JobCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FreelancerController extends Controller
{

    public function freelancerdash()
    {
       
        $alljobs  = Job::where('status', 'active')->get();

        $activejobs  = Job::where(['freelancer_assigned_id'=>auth()->user()->id,'status'=>'assigned'])->get();

        $completetasks  = Job::where(['freelancer_assigned_id'=>auth()->user()->id,'status'=>'complete'])->get();

        $recentTasks = Job::orderBy('created_at', 'desc')->take(5)->get();
        $completedfivetasks = Job::query()->where(['freelancer_assigned_id'=>auth()->user()->id,'status'=>'complete'])->orderBy('created_at', 'desc')->take(5)->get();

        $jobsThisWeek = Job::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();

        $jobsThisMonth = Job::select('*')
            ->whereMonth('created_at', Carbon::now()->month)
            ->get();
        $graphJobs = Job::all();
            // ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            // ->groupBy('day')
            // ->get();    


        return view('freelancer.dashboard', compact('alljobs', 'activejobs', 'completetasks','recentTasks', 'completedfivetasks', 'jobsThisWeek', 'jobsThisMonth', 'graphJobs'));
    }
   
    public function allJobs(){
        
        $jobs  = Job::where('status', 'active')->get();

        return view('freelancer.jobs.alljobs', compact('jobs'));
    }

    public function singlejob($id)
    {

        $job = Job::findOrFail($id);
       

        return view('freelancer.jobs.singlejob', compact('job'));
    }


    public function completejobs()
    
    {
        $job  = Job::where(['freelancer_assigned_id'=>auth()->user()->id,'status'=>'complete'])->get();

        
        

        return view('freelancer.jobs.compeletejobs', compact('job'));
    }


    public function pendingjobs()
    {
        $job  = Job::where(['freelancer_assigned_id'=>auth()->user()->id,'status'=>'complete'])->get();

        

        return view('freelancer.jobs.pendingjobs', compact('job'));
    }

    public function allpayments(){
        

        return view('freelancer.payments.allpayments');
    }
    public function completepayments(){
        

        return view('freelancer.payments.completepayments');

    }
    public function pendingpayments(){
        

        return view('freelancer.payments.pendingpayments');
        
    }
    public function disputedpayments(){
        

        return view('freelancer.payments.disputedpayments');
        
    }
}
