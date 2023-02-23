@extends('layouts.main');
@section('title', 'All Drafts')
@section('content')

    <div>
        <div class="col-xl-12">
            <div class="card card-one">
                <div class="card-header">
                    <h6 class="card-title">All Draft Jobs</h6>
                    <nav class="nav nav-icon nav-icon-sm ms-auto">
                        <a href="#" class="nav-link"><i class="ri-refresh-line"></i></a>
                        <a href="#" class="nav-link"><i class="ri-more-2-fill"></i></a>
                    </nav>
                </div><!-- card-header -->
                <div class="card-body p-3">

                    <table id="example" class="display nowrap table-bodered" style="width:100%">
                        <thead>
                            <th>id</th>
                            <th>Headline</th>
                            <th>Title</th>
                            <th>Action</th>
                        </thead>
                        <tbody>
                            <div class="product-wrapper">
                                @foreach ($drafts as $key => $job)
                                    <tr>
                                        <td>{{ ++$key }}</td>
                                        <td>{{ $job->headline }}</td>
                                        <td>{{ $job->title }}</td>
                                        <td>
                                            <a href="{{ route('superadmin.singlejob', $job->id) }}"><button
                                                    class="btn btn-sm btn-primary">View Draft</button></a>
                                        </td>
                                    </tr>
                                @endforeach
                            </div>

                        </tbody>
                    </table>

                </div><!-- card-body -->
            </div><!-- card -->
        </div><!-- col -->

    </div>

@endsection
