@extends('layouts.main');
@section('title', 'Creating New Job')
@section('content')
    <div class="">
        <a href="{{ route('user.alljobs') }}"><button type="submit" class="btn btn-primary">Back</button></a>
        <h3 class="text-center">Edit Job Details</h3>
    </div>

    <form method="post" action="{{ route('user.update-single-job', $job->id )}}" >
        @csrf
        <div class="row g-3 mb-2">
            <div class="col-sm-12">
                <label class="form-label">HeadLine</label>
                <input type="text" class="form-control " name="headline"
                    value="{{ $job->headline }}">
                
            </div><!-- col -->
        </div>
        <div class="row g-3">

            <div class="col-sm-8">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" name="title" value="{{ $job->title }}">
                
            </div>
            <div class="col-sm-4">
                <label class="form-label">Job Category</label>
                <select class="form-control" name="category_name"  value="{{ $job->category_id }}">
                    <option value="">Click to select</option>
                    @foreach ($jobcategories as $item)
                        <option value="{{ $item->id }}">{{ $item->category_name }}</option>
                    @endforeach
                </select>
                
            </div>

        </div>
        <div class="row g-3">
            <div class="col-sm-12">
                <label class="form-label">Description</label>
                <input type="text" class="form-control" name="description"
                    value="{{ $job->description }}">
                
            </div><!-- col -->
            <div class="col-12">
                <label class="form-label">Skills</label>
                <textarea rows="2" type="text" class="form-control" name="skills"
                    value="{{ $job->skills }}"> </textarea>
                
            </div><!-- col -->
        </div>

        <div class="row g-3 mt-3">
            <h4 class="mt-3">Payment</h4>
            <div class="col-xm-12 d-flex">

                <div class="col-sm-6 mr-4">
                    <label class="form-label">Hourly</label>
                    <input type="text" placeholder="$/Hour"
                        class="form-control " name="hourly_pay" value="{{ $job->hourly_pay }}">
                    
                </div><!-- col -->

                <div class="col-sm-6">
                    <label class="form-label">Per Project</label>
                    <input type="text" placeholder="$/Project"
                        class="form-control" name="project_pay" value="{{ $job->project_pay }}">

                </div><!-- col -->

            </div>
            <div class="col-sm-2">
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
    </form>
@endsection
