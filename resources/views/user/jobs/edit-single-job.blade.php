@extends('layouts.main');
@section('title', 'Creating New Job')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <div>
        <ol class="breadcrumb fs-sm mb-1">
            <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
        </ol>
        <h4 class="main-title mb-0">Welcome to Dashboard</h4>
    </div>

    <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
        <button type="button" class="btn btn-white btn-icon"><i
                class="ri-share-line fs-18 lh-1"></i></button>
        <button type="button" class="btn btn-white btn-icon"><i
                class="ri-printer-line fs-18 lh-1"></i></button>
        <button type="button" class="btn btn-primary d-flex align-items-center gap-2"><i
                class="ri-bar-chart-2-line fs-18 lh-1"></i>Generate<span class="d-none d-sm-inline">
                Report</span></button>
    </div>
</div>
    @livewire('user.jobs.create-new-job')
@endsection