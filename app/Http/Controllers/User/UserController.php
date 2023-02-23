<?php

namespace App\Http\Controllers\User;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobCategory;
use App\Models\Job;

use Brian2694\Toastr\Facades\Toastr;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:user']);
    }
    public function checkauth()
    {
        $user = auth()->user();
        return $user;
    }

    public function userdash()
    

    {
        $postedjobs  = Job::query()
                            ->where(['user_id'=>auth()->user()->id,'status'=>'active'])
                            ->get();

        $completedtasks = Job::query()
                            ->where(['user_id'=>auth()->user()->id,'status'=>'complete'])
                            ->get();
        $tasksInProgress = Job::query()
                            ->where(['user_id'=>auth()->user()->id,'status'=>'pending'])
                            ->get();
        $tasksInDrafts = Job::query()
                            ->where(['user_id'=>auth()->user()->id,'status'=>'draft'])
                            ->get();
        $recentTasks = Job::query()
                            ->where(['user_id'=>auth()->user()->id,'status'=>'complete'])
                            ->get();
        $completedrecenttasks = Job::query()->where(['user_id'=>auth()->user()->id,'status'=>'complete'])
                            ->orderBy('created_at', 'desc')->take(5)->get();
 

        return view('user.dashboard', compact('postedjobs', 'completedtasks', 'tasksInProgress', 'tasksInDrafts', 'completedrecenttasks'));
    }
    public function newJob()
    {
        return view('user.jobs.create-new-job');
    }
    public function newJobattachments($slug)
    {
       $checktask = Job::where(['job_id'=>$slug, 'user_id'=>auth()->user()->id])->first();
       if($checktask){
            return view('user.jobs.attachments', compact('slug'));
       }else{
        Toastr::warning('An error occured', 'Alert', ["positionClass" => "toast-bottom-right"]);
        return redirect()->route('user.alljobs');
       }
    }
    public function publishjob($slug)
    {
       $checktask = Job::where(['job_id'=>$slug, 'user_id'=>auth()->user()->id])->first();
       $checktask->status="active";
       $checktask->save();
        Toastr::success('Job is now visible on the main website', 'Congrats', ["positionClass" => "toast-bottom-right"]);
        return redirect()->route('user.alljobs');

    }
    public function allJobs()
    {

        $user = Auth::user();
        $userId = $user->id;
        $jobs = Job::where('user_id', $userId)->get();

        return view('user.jobs.alljobs', compact('jobs'));
    }

    public function singleJob($id)
    {

        $job = Job::findOrFail($id);
        $jobcategories = JobCategory::all();

        return view('user.jobs.single-job', compact(['job', 'jobcategories']));
    }
    public function UpdateSingleJob(Request $request, $id)
    {
        $request->validate([
            'headline' => 'required',
            'title' => 'required',
            'description' => 'required',
            'skills' => 'required',
            'hourly_pay' => 'required',
            'project_pay' => 'required',

        ]);
        $job = Job::findOrFail($id);
        $job->headline = $request->headline;
        $job->title = $request->title;
        $job->user_id = auth()->user()->id;
        $job->category_id = $request->category_name;
        $job->description = $request->description;
        $job->skills = $request->skills;
        $job->hourly_pay = $request->hourly_pay;
        $job->project_pay = $request->project_pay;
        $job->status = 'draft';
        $job->save();
        return redirect()->route('user.alljobs')->with('success', 'Job Details updated successfully');
        
    }
    ///repeat
    public function DeleteSingleJob($id)
    {
        $job = Job::findOrFail($id);
        $job->delete();
        return redirect()->route('user.alljobs')->with('success', 'Job Deleted successfully');
    }

    public function draftjobs()
    {
        $draftjobs = Job::query()
        ->where(['user_id'=>auth()->user()->id,'status'=>'draft'])
        ->get();
        return view('user.jobs.draftjobs', compact('draftjobs'));

    }
    public function jobsinprogress()
    {
        $jobsinprogress = Job::query()
        ->where(['user_id'=>auth()->user()->id,'status'=>'inprogress'])
        ->get();
        return view('user.jobs.jobsinprogress', compact('jobsinprogress'));

    }
    public function completejobs()
    {
        $completejobs = Job::query()
        ->where(['user_id'=>auth()->user()->id,'status'=>'complete'])
        ->get();
        return view('user.jobs.completejobs', compact('completejobs'));

    }
    public function allpayments()
    {
        
        return view('user.payments.allpayments');

    }
}
