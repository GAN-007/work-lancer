@extends('layouts.main');
@section('title', 'All-Job Categories')
@section('content')

    <div>
        <div class="col-xl-12">
            <div class="card card-one">
                <div class="card-header">
                    <h6 class="card-title">All My Jobs</h6>
                    <nav class="nav nav-icon nav-icon-sm ms-auto">
                        <a href="#" class="nav-link"><i class="ri-refresh-line"></i></a>
                        <a href="#" class="nav-link"><i class="ri-more-2-fill"></i></a>
                    </nav>
                </div><!-- card-header -->
                <div class="card-body p-3">

                    <table id="example" class="display nowrap table-bodered" style="width:100%">
                        <thead>
                            <th>id</th>
                            <th>Category Name</th>
                            <th>Action</th>
                        </thead>
                        <tbody>
                            <div class="product-wrapper">
                                @foreach ($jobcategories as $key => $item)
                                    <tr>
                                        <td>{{ ++$key }}</td>
                                        <td>{{ $item->category_name }}</td>
                                        <td>
                                            <a href="{{ route('superadmin.deletejobcategory', $item->id)}}"><button
                                                    class="btn btn-sm btn-warning">Delete</button></a>

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
