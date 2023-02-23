@extends('layouts.main');
@section('title', 'All Drafts Jobs')
@section('content')

    <div>
        <div class="col-xl-12">
            <div class="card card-one">
                <div class="card-header">
                    <h6 class="card-title">All Drafts</h6>
                </div><!-- card-header -->
                <div class="card-body p-3">

                    <table id="example" class="display nowrap table-bodered" style="width:100%">
                        <thead>
                            <th>id</th>
                            <th>Headline</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Action</th>
                        </thead>
                        <tbody>
                            <div class="product-wrapper">
                                @foreach ($draftjobs as $key => $job)
                                    <tr>
                                        <td>{{ ++$key }}</td>
                                        <td>{{ $job->headline }}</td>
                                        <td>{{ $job->title }}</td>
                                        <td>{{ $job->status }}</td>
                                        <td>
                                            <a href="{{ route('user.single-job',$job->id) }}"><button class="btn btn-sm btn-primary">Edit</button></a>
                                            <a href="{{ route('user.delete-single-job',$job->id) }}"><button class="btn btn-sm btn-danger">Delete</button></a>                                           
                                            
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
