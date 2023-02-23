@extends('layouts.main');
@section('title', 'single Job')
@section('content')
    <a href="{{ route('superadmin.alljobs') }}"><button type="submit" class="btn btn-primary">Back</button></a>
    <div class="col--md-12">

        <div class="card">
            <div class="card-header">
                <h6 class="card-title">Title</h6>

            </div>
            <div class="card-body">
                {{ $job->title }}
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">Headline</h6>

            </div>
            <div class="card-body">
                {{ $job->headline }}
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">Description</h6>

            </div>
            <div class="card-body">
                {{ $job->description }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">Skills Needed</h6>

            </div>
            <div class="card-body">
                {{ $job->skills }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">Payment category:</h6>

            </div>
            <div class="card-body">
                {{ $job->payment_category }}
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title">Payrate:</h6>

            </div>
            <div class="card-body">
                {{ $job->pay_rate }}
            </div>
        </div>
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title">Date Posted</h6>

                </div>
                <div class="card-body">
                    {{ $job->created_at }}
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h6 class="card-title">Task Deadline</h6>

                </div>
                <div class="card-body">
                    {{ $job->task_deadline }}
                </div>
            </div>
        </div>
    </div>


    <!--- col -->
@endsection
