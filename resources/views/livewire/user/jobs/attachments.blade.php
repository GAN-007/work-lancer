<div>
    <div id="wizard1" class="wizard-demo wizard clearfix" role="application">


        <div class="content clearfix">

            <h3 id="wizard1-h-0" tabindex="-1" class="title current">Uploading Task Attachments</h3>
            <div id="wizard1-p-0" role="tabpanel" aria-labelledby="wizard1-h-0" class="body current" aria-hidden="false">
                <div class="row g-3">
                    <div class="col-lg-5 col-md-5 col-xs-12 col-sm-12">

                    </div><!-- col -->
                    <div class="col-lg-7 col-md-7 col-xs-12 col-sm-12">

                        <form wire:submit.prevent="submitattachment" style="padding:20px 10px;border:1px solid #08f44b;"
                            enctype="multipart/form-data">
                            @csrf
                            @if (session()->has('message'))
                            <div class="alert alert-success mx-4 p-1">
                                <span class="text-success">{{ session('message') }}</span>
                            </div>
                        @endif
                        @if (session()->has('delete'))
                        <div class="alert alert-danger mx-4 p-1">
                            <span class="text-danger">{{ session('delete') }}</span>
                        </div>
                    @endif
                            <div class="row">
                                <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label" for="noofsources">Upload Attachment</label>
                                        <input type="file" wire:model="file_attachment"
                                            id="noofsources"placeholder="Upload attachment files here"
                                            class="form-control @error('file_attachment') is-invalid @enderror">
                                        @error('file_attachment')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                        <small class="form-text text-success">Only upload
                                            jpeg,jpg,png,pdf,doc,docx,xls,xlsx,ppt,pptx files</small>
                                    </div>
                                </div>
                                <div class="col-lg-12 col-md-12 col-xs-12 col-sm-12">
                                    <div class="form-group" class="d-flex flex-row justify-content-end">
                                        <button type="submit" class="btn btn-sm btn-success mt-2">Upload
                                            Attachment</button>
                                        <a href="{{ route('user.publish-job', $job->job_id) }}" class="text-danger">Skip and Publish This Task</a>
                                    </div>

                                </div>
                            </div>

                        </form>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th></th>
                                        <th>FIle Name</th>
                                        <th class="text-nowrap">Date Uploaded</th>
                                        <th>File Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($attachments as $key=>$item)
                                        <tr style="font-size: 12px;">
                                            <td>{{ ++$key }}</td>
                                            <td>{{ $item->image }}</td>
                                            <td>{{ $item->created_at->format('M, d y,  H:i A') }}</td>
                                            <td>{{ $item->attachment_type }}</td>
                                            <td>

                                                <div class="d-flex align-items-center col-actions">
                                                    <a class="me-1"
                                                        href="#"
                                                        data-bs-toggle="tooltip" data-bs-placement="top" title=""
                                                        data-bs-original-title="Download Job Attachment"
                                                        aria-label="Order Attachment">
                                                        <i class="ri-arrow-down-fill"></i>
                                                    </a>
                                                    <button class="btn btn-danger" wire:click="deletefile({{ $item->id}})">
                                                        <i class="ri-government-fill"></i>
                                                    </button>

                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No Attachments Found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        </div>
                    </div>

                    <!-- col -->
                </div><!-- form-row -->
            </div>


        </div>



    </div>
</div>
