<div>
    <div id="wizard1" class="wizard-demo wizard clearfix" role="application">
        <form wire:submit.prevent="addJobDetails" method="post" autocomplete="off" class="p-4">


            <div class="content clearfix">
                @if ($currentstep == 1)
                    <h3 id="wizard1-h-0" tabindex="-1" class="title current">Create New Task</h3>
                    <div id="wizard1-p-0" role="tabpanel" aria-labelledby="wizard1-h-0" class="body current"
                        aria-hidden="false">
                        <div class="row g-3">
                            <div class="col-sm-12">
                                <label class="form-label">Write Headline for your job Post </label>
                                <input type="text" wire:model="headline"
                                    class="form-control @error('headline') is-invalid @enderror"
                                    placeholder="Build responsive Wordpress site with booking/payment functionality">
                                @error('headline')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div><!-- col -->
                            <div class="col-sm-12">
                                <label class="form-label">Add a Title</label>
                                <input type="text" wire:model="title"
                                    class="form-control @error('title') is-invalid @enderror"
                                    placeholder="Write a clear title for your job">
                                @error('title')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-sm-12">
                                <label class="form-label">Job Category</label>
                                <select wire:model="category_name"
                                    class="form-control @error('category_name') is-invalid @enderror">
                                    <option value="">Click to select</option>
                                    @foreach ($jobcategories as $item)
                                        <option value="{{ $item->id }}">{{ $item->category_name }}</option>
                                    @endforeach
                                </select>
                                @error('category_name')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div>
                            <!-- col -->
                        </div><!-- form-row -->
                    </div>
                @endif
                @if ($currentstep == 2)
                    <h3 id="wizard1-h-1" tabindex="-1" class="title">Describing Your Task</h3>
                    <div id="wizard1-p-1" role="tabpanel" aria-labelledby="wizard1-h-1" class="body"
                        aria-hidden="true">
                        <p class="mb-4">Description</p>
                        <div class="row g-3">
                            <div class="col-sm-12">
                                <label class="form-label">Description</label>
                                <textarea wire:model="description" rows="5" cols="10"
                                    class="form-control @error('description') is-invalid @enderror"
                                    placeholder="Already have a job description? Paste it here....."></textarea>
                                @error('description')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div><!-- col -->
                            <div class="col-12">
                                <label class="form-label">Which Skills Should the freelancer have?</label>
                                <textarea rows="2" type="text" wire:model="skills" class="form-control @error('skills') is-invalid @enderror"> placeholder="Enter skills"></textarea>
                                @error('skills')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div><!-- col -->
                        </div><!-- row -->
                    </div>
                @endif
                @if ($currentstep == 3)
                    <h3 id="wizard1-h-2" tabindex="-1" class="title">Payment Details</h3>
                    <div id="wizard1-p-2" role="tabpanel" aria-labelledby="wizard1-h-2" class="body"
                        aria-hidden="true">
                        <p class="mb-4"> Project Budget</p>
                        <div class="row g-3">
                            <div class="col-md-4 col-lg-4 col-xs-12 col-sm-12">
                                <div class="form-group">
                                    <label for="">Pay Per</label>
                                    <select wire:model="payment_category" id=""
                                        class="form-control @error('payment_category') is-invalid @enderror">
                                        <option value="">click to select</option>
                                        <option value="Per Hour">Per Hour</option>
                                        <option value="Complete Project">Complete Project</option>
                                    </select>
                                    @error('payment_category')
                                        <small style="color:red;">{{ $message }}</small>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4 col-lg-4 col-xs-12 col-sm-12">
                                <label class="form-label">Amount</label>
                                <input type="text" wire:model="pay_rate" placeholder="$/Hour"
                                    class="form-control @error('pay_rate') is-invalid @enderror">
                                @error('pay_rate')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div><!-- col -->
                            <div class="col-md-4 col-lg-4 col-xs-12 col-sm-12">
                                <label class="form-label">Task Deadline</label>
                                <input type="datetime-local" wire:model="task_deadline" placeholder="$/Project"
                                    class="form-control @error('task_deadline') is-invalid @enderror">
                                @error('task_deadline')
                                    <small style="color:red;">{{ $message }}</small>
                                @enderror
                            </div><!-- col -->

                        </div><!-- row -->
                    </div>
                @endif
            </div>
            {{-- <div class="actions clearfix">
                <ul role="menu" aria-label="Pagination">
                    <li class="disabled" aria-disabled="true"><a href="#previous" role="menuitem">Previous</a></li>
                    <li aria-hidden="false" aria-disabled="false"><a href="#next" role="menuitem">Next</a></li>
                    <li aria-hidden="true" style="display: none;"><a href="#finish" role="menuitem">Finish</a></li>
                </ul>
            </div> --}}
            <div class="actions clearfix">


                @if ($currentstep == 1)
                    <ul role="menu" aria-label="Pagination" class="d-flex flex-row justify-content-end p-3">
                        <button type="button" class="btn btn-primary mt-3" role="menuitem"
                            wire:click="increasestep()">Next Step</button>
                    </ul>
                @endif
                @if ($currentstep == 2)
                    <ul role="menu" aria-label="Pagination" class="d-flex flex-row justify-content-end p-3">
                        <button type="button" name="backward" class="btn btn-warning mx-2"
                            wire:click="descreaseStep()">Previous</button>
                        <button type="button" name="forward" class="btn btn-primary"
                            wire:click="increasestep()">Next Step</button>
                    </ul>
                @endif
                @if ($currentstep == 3)
                    <ul role="menu" aria-label="Pagination" class="d-flex flex-row justify-content-end p-3">
                        <button type="button" name="backward" class="btn btn-warning mx-2"
                            wire:click="descreaseStep()">Previous</button>
                        <button type="submit" name="forward" class="btn btn-success"
                            wire:click="increasestep()">Submit Task</button>
                    </ul>
                @endif

            </div>

    </div>
</div>
