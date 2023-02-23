@extends('layouts.main')
@section('title', 'Employer Dashboard')
@section('content')
    <div class="row g-3">
        <div class="col-md-6">
            <div class="row g-3">
                <div class="col-6 col-md-6 col-xl-6">
                    <div class="card card-one card-product">
                        <div class="card-body p-3">

                            <h2 class="card-value ls--1"><span></span>{{ $postedjobs->count() }}</h2>
                            <label class="card-label fw-medium text-dark">Posted Tasks</label>

                        </div><!-- card-body -->
                    </div><!-- card -->
                </div><!-- col -->
                <div class="col-6 col-md-6 col-xl-6">
                    <div class="card card-one card-product">
                        <div class="card-body p-3">

                            <h2 class="card-value ls--1"><span></span>{{ $completedtasks->count() }}</h2>
                            <label class="card-label fw-medium text-dark">Completed Tasks</label>

                        </div><!-- card-body -->
                    </div><!-- card -->
                </div><!-- col -->
            </div><!-- row -->
        </div>
        <div class="col-md-6">
            <div class="row g-3">
                <div class="col-6 col-md-6 col-xl-6">
                    <div class="card card-one card-product">
                        <div class="card-body p-3">

                            <h2 class="card-value ls--1">{{ $tasksInProgress->count() }}</h2>
                            <label class="card-label fw-medium text-dark"> Tasks In Progress</label>

                        </div><!-- card-body -->
                    </div><!-- card -->
                </div><!-- col -->
                <div class="col-6 col-md-6 col-xl-6">
                    <div class="card card-one card-product">
                        <div class="card-body p-3">

                            <h2 class="card-value ls--1">{{ $tasksInDrafts->count() }}</h2>
                            <label class="card-label fw-medium text-dark">Draft Tasks</label>

                        </div><!-- card-body -->
                    </div><!-- card -->
                </div><!-- col -->
            </div>
        </div>
    </div>


@endsection
