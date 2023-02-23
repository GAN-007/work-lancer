@extends('layouts.main');
@section('title', 'All Freelancers')
@section('content')

    <div>
        <div class="col-xl-12">
            <div class="card card-one">
                <div class="card-header">
                    <h6 class="card-title">All Freelancers</h6>
                                   
                </div><!-- card-header -->
                <div class="card-body p-3">

                    <table id="example" class="display nowrap table-bodered" style="width:100%">

                        <thead>
                            <th>id</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>Last Update</th>
                        </thead>
                        <tbody>
                            <div class="product-wrapper">
                                @foreach ($users as $key => $user)
                                    <tr>
                                        <td>{{ ++$key }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->created_at}}</td>
                                        <td>{{ $user->updated_at}}</td>
                                        
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
