<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use App\Models\Job;
use App\Models\User;
use Carbon\Carbon;
use Brian2694\Toastr\Facades\Toastr;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:superadmin']);
    }
    public function checkauth()
    {
        $user = auth()->user();
        return $user;
    }
    public function superadmindash(){

        $freelancers = User::whereRoleIs('freelancer')->get();

        $employers = User::whereRoleIs('user')->count();

        $completeTasks = Job::where('status', 'complete')->get();

        $recentfivecompleteTasks = Job::where('status', 'complete')->orderBy('created_at', 'desc')->take(5)->get();

        $recentfiveTasks = Job::where('status', 'active')->orderBy('created_at', 'desc')->take(5)->get();

        $tasksInProgress = Job::where('status', 'inprogress')->get();

        $jobsThisWeek = User::whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])->get();

        $jobsThisMonth = Job::select('*')
            ->whereMonth('created_at', Carbon::now()->month)
            ->get();

        $graphJobs = Job::all();

        return view('superadmin.dashboard', compact('freelancers', 'employers', 'completeTasks', 'tasksInProgress', 'jobsThisWeek', 'graphJobs', 'jobsThisMonth', 'recentfivecompleteTasks', 'recentfiveTasks'));
    }
    public function createcategory(){
        return view('superadmin.jobs.createcategory');
    }

    public function createJobcategory(Request $request){
        $request->validate([
            'category_name' => ['required', 'string', 'max:255'],
            
        ]);
        $new = new JobCategory;
        $new->category_name = $request->category_name;       
        $new->save();

        Toastr::success('Job is now visible on the main website', 'Congrats', ["positionClass" => "toast-bottom-right"]);
        return redirect()->route('superadmin.create.category');
    
    }

    public function alljobcategories(){
        $jobcategories = JobCategory::all();

        return view('superadmin.jobs.alljobcategories', compact('jobcategories'));
    }
    
    
    public function deletejobcategory($id)
    {
        $item = JobCategory::findOrFail($id);
        $item->delete();
        return redirect()->route('superadmin.alljobcategories')->with('success', 'Job Category Deleted successfully');
    }

    public function alljobs()
    {
        $jobs = Job::all();

        return view('superadmin.jobs.alljobs', compact('jobs'));
    }

    public function viewjob($id)
    {
        $job = Job::findOrFail($id);

        return view('superadmin.jobs.singlejob', compact('job'));
    }
    public function deletejob($id)
    {
        $item = Job::findOrFail($id);
        $item->delete();
        return redirect()->route('superadmin.alljobs')->with('success', 'Task Deleted successfully');
    }

    public function allcompletejobs()
    {    

        $completejobs = Job::where('status', 'complete')
        ->get();
        return view('superadmin.jobs.completejobs', compact('completejobs'));

    }
    public function jobsinprogress()
    {    

        $jobsinprogress = Job::where('status', 'pending')
        ->get();
        return view('superadmin.jobs.tasksinprogress', compact('jobsinprogress'));

    }

    public function jobsasdrafts()
    {    

        $drafts = Job::where('status', 'draft')
        ->get();
        return view('superadmin.jobs.drafts', compact('drafts'));

    }
    public function allpayments()
    {    

        $allpayments = Job::where('status', 'draft')
        ->get();
        return view('superadmin.payments.allpayments', compact('allpayments'));

    }
    public function allemployers()
    {
        $users = User::whereRoleIs('user')->get();
        return view('superadmin.users.allemployers', compact('users'));
    }
    public function allfreelancers()
    {
        $users = User::whereRoleIs('freelancer')->get();
        return view('superadmin.users.all-freelancers', compact('users'));
    }
}
