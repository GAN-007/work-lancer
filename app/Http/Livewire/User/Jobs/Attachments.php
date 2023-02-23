<?php

namespace App\Http\Livewire\User\Jobs;

use App\Models\Job;
use App\Models\JobAttachment;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class Attachments extends Component
{
    use WithFileUploads;
    public  $jobid;
    public $file_attachment;

    public function mount($jobslug)
    {
        $this->jobid = $jobslug;
    }
    public function render()
    {
        $job = Job::where('job_id', $this->jobid)->first();
        $attachments = JobAttachment::where(['job_id' => $job->id, 'category' => 'attachment'])->get();
        return view('livewire.user.jobs.attachments', compact('job', 'attachments'));
    }
    public function submitattachment()
    {
        $this->validate([
            'file_attachment' => 'required|mimes:jpeg,jpg,png,pdf,doc,docx,xls,xlsx,ppt,pptx|max:100048',
        ]);
        $order = Job::where('job_id', $this->jobid)->first();
        if ($order == null) {
            session()->flash('message', 'File Attachment successfully uploaded.');
        } else {

            $attachment  = new JobAttachment;
            $attachment->job_id = $order->id;
            $fileNameWithExt = $this->file_attachment->getClientOriginalName();
            $fileName =  pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            $Extension = $this->file_attachment->getClientOriginalExtension();
            $filenameToStore = $fileName . '-' . $order->order_id . '.' . $Extension;
            $path = $this->file_attachment->storeAs('job_files', $filenameToStore, 'public');
            $attachment->image = $filenameToStore;
            $attachment->attachment_type = $Extension;
            $attachment->category = "attachment";
            $attachment->file_size = number_format($this->file_attachment->getSize() / 1048576, 2);
            $attachment->save();
            $this->file_attachment = null;
            session()->flash('message', 'File Attachment successfully uploaded.');
        }
    }
    public function deletefile($id)
    {
        $task = JobAttachment::where('id', $id)->first();
        Storage::delete('public/job_files/' . $task->image);
        $task->delete();
        session()->flash('delete', 'File Attachment deleted successfully.');
    }
}
