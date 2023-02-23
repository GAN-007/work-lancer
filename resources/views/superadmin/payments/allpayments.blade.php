@extends('layouts.main');
@section('title', 'All-Payments')
@section('content')

    <div>
        <div class="col-xl-12">
            <div class="card card-one">
                <div class="card-header">
                    <h6 class="card-title">All Payments</h6>
                    
                    
                </div><!-- card-header -->
                <div class="card-body p-3">

                    <table id="example" class="display nowrap table-bodered" style="width:100%">
                        <thead>
                            <th>id</th>
                            <th>Task_id</th>
                            <th>Title</th>
                            <th>Amount Paid</th>
                            <th>Date Paid</th>
                            <th>Amount Pending</th>
                        </thead>
                        <tbody>
                            <div class="product-wrapper">
                                
                            </div>

                        </tbody>
                    </table>

                </div><!-- card-body -->
            </div><!-- card -->
        </div><!-- col -->

    </div>

@endsection
