<?php

namespace App\Http\Livewire\User\Jobs;

use App\Models\JobCategory;
use App\Models\Job;
use Carbon\Carbon;
use Livewire\Component;
use Brian2694\Toastr\Facades\Toastr;

class CreateNewJob extends Component
{
    public $totalsteps = 3;
    public $currentstep = 1;
    public $headline;
    public $title;
    public $category_name;
    public $description;
    public $skills;
    public $hourly_pay;
    public $project_pay;
    public $status;
    public $payment_category;
    public $pay_rate;
    public $task_deadline;

    public function mount()
    {
        $this->currentstep = 1;
    }

    public function increasestep()
    {
        $this->resetErrorBag();
        $this->validateData();
        $this->currentstep++;
        if ($this->currentstep > $this->totalsteps) {
            $this->currentstep = $this->totalsteps;
        }
    }
    public function descreaseStep()
    {
        $this->resetErrorBag();
        $this->currentstep = $this->currentstep - 1;
        if ($this->currentstep < 1) {
            $this->currentstep = 1;
        }
    }

    // public function render()
    // {
    //     return view('livewire.user.jobs.create-new-job');
    // }
    public function render()
    {
        $jobcategories = JobCategory::all();

        return view('livewire.user.jobs.create-new-job', compact('jobcategories'));
    }

    public function validateData()
    {
        if ($this->currentstep == 1) {
            $this->validate([
                'headline' => 'required|string|max:300',
                'title' => 'required|string|max:300',
                'category_name' => 'required|string|max:300',

            ], [
                'headline.required' => 'Please provide a valid Headline',
                'title.required' => 'Please provide a valid title',
                'category_name.required' => 'Please select a category',

            ]);
        } else if ($this->currentstep == 2) {
            $this->validate([
                'description' => 'required|string|max:300',
                'skills' => 'required|string|max:300',


            ]);
        }
    }
    public function addJobDetails()
    {
        $this->resetErrorBag();
        if ($this->currentstep ==  3) {

            $this->validate([
                'payment_category' => 'required',
                'pay_rate' => 'required|numeric|min:5',
                'task_deadline' => 'required|after_or_equal:' . Carbon::now()->addHours(6),

            ]);
        }

        $time = time();
        $subtime = substr($time, 5);
        $taskid = auth()->user()->id . '' . $subtime;

        $new = new Job;
        $new->headline = $this->headline;
        $new->title = $this->title;
        $new->user_id = auth()->user()->id;
        $new->category_id = $this->category_name;
        $new->description = $this->description;
        $new->skills = $this->skills;
        $new->payment_category = $this->payment_category;
        $new->pay_rate = $this->pay_rate;
        $new->status = 'draft';
        $new->job_id = $taskid;
        $new->task_deadline = $this->task_deadline;
        $new->save();

        Toastr::success('New task created successfully', 'Success', ["positionClass" => "toast-bottom-right"]);
        return redirect()->route('user.new-job-attachments', $new->job_id);
    }
}
