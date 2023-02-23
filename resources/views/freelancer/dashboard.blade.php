@extends('layouts.main')
@section('title', 'Freelancer | Dashboard')
@section('content')

    <div class="col-6 col-xl-3">
        <div class="card card-one">
            <div class="card-body">
                <label class="card-title fs-sm fw-medium mb-1">All Jobs</label>
                <h3 class="card-value mb-1"><i class="ri-shopping-bag-3-line"> {{ $alljobs->count() }}</i>
                </h3>

            </div><!-- card-body -->
        </div><!-- card-one -->
    </div><!-- col -->

    <div class="col-6 col-xl-3">
        <div class="card card-one">
            <div class="card-body">
                <label class="card-title fs-sm fw-medium mb-1">Active Tasks</label>

                <h3 class="card-value mb-1"><i class="ri-briefcase-4-line"></i>{{ $activejobs->count() }} </h3>

            </div><!-- card-body -->
        </div><!-- card-one -->
    </div><!-- col -->

    <div class="col-6 col-xl-3">
        <div class="card card-one">
            <div class="card-body">
                <label class="card-title fs-sm fw-medium mb-1">Pending Payements</label>
                <h3 class="card-value mb-1"><i class="ri-inbox-line"></i> 4,598</h3>

            </div><!-- card-body -->
        </div><!-- card-one -->
    </div><!-- col -->
    <div class="col-6 col-xl-3">
        <div class="card card-one">
            <div class="card-body">
                <label class="card-title fs-sm fw-medium mb-1">Completed Tasks</label>
                <h3 class="card-value mb-1"><i class="ri-bar-chart-box-line"></i> {{ $completetasks->count() }}</h3>

            </div><!-- card-body -->
        </div><!-- card-one -->
    </div><!-- col -->
    <div class="col-xl-12">
        <div class="card card-one">
            <div class="card-header">
                <h6 class="card-title">Jobs Trend</h6>

            </div><!-- card-header -->
            <div class="card-body">



                <div id="barchart_material" style="width: 100%; height: 500px;"></div>
                <script type="text/javascript">
                    google.charts.load('current', {
                        'packages': ['bar']
                    });
                    google.charts.setOnLoadCallback(drawChart);

                    function drawChart() {
                        var data = google.visualization.arrayToDataTable([
                            ['Job ID', 'Price', 'Headline'],

                            @php
                                foreach ($graphJobs as $job) {
                                    echo "['" . $job->id . "', '" . $job->pay_rate . "', '" . $job->headline . "'],";
                                }
                            @endphp
                        ]);

                        var options = {
                            chart: {
                                title: 'Bar Graph | Price',
                                subtitle: 'Job ID, and Price: @php echo $graphJobs[0]->created_at @endphp',
                            },
                            bars: 'vertical'
                        };
                        var chart = new google.charts.Bar(document.getElementById('barchart_material'));
                        chart.draw(data, google.charts.Bar.convertOptions(options));
                    }
                </script>
                <div class="p-2">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <h3 class="card-value mb-2"><span></span>{{ $jobsThisWeek->count() }}</h3>
                            <label class="card-title fw-semibold text-dark mb-2">Posted This Week</label>



                        </div>
                        <div class="col-sm-6">
                            <h3 class="card-value mb-2"><span></span>{{ $jobsThisMonth->count() }}</h3>
                            <label class="card-title fw-semibold text-dark mb-2">Posted This Month</label>

                        </div>
                    </div>
                </div>
            </div><!-- card-body -->
        </div><!-- card -->
    </div><!-- col -->

    <div class="col-md-6 col-xl-4">
        <div class="card card-one">
            <div class="card-header">
                <h6 class="card-title">Recent 5 Jobs</h6>

            </div><!-- card-header -->
            <div class="card-body p-0">
                <ul class="people-group">
                    @foreach ($recentTasks as $task)
                        <li class="people-item">
                            <div class="avatar"><span class="avatar-initial bg-teal fs-20"></span></div>
                            <div class="people-body">
                                <h6><a href="{{ route('freelancer.singlejob', $task->id) }}">{{ str_limit(strip_tags($task->title), 30) }}
                                        @if (strlen(strip_tags($task->title)) > 30)
                                            ....
                                        @endif
                                    </a></h6>
                                <span>Task ID - {{ $task->job_id }}</span>
                            </div><!-- people-body -->
                            <div class="text-end">
                                <div class="fs-sm"> {{ $task->payment_category }} - $ {{ $task->pay_rate }}</div>
                                <span class="d-block fs-xs text-success">{{ $task->status }}</span>
                            </div>
                        </li>
                    @endforeach



                </ul>
            </div><!-- card-body -->
            <div class="card-footer d-flex justify-content-center">
                <a href="{{ route('freelancer.postedjobs') }}" class="fs-sm">See All Jobs</a>
            </div><!-- card-footer -->
        </div><!-- card -->
    </div><!-- col -->
    <div class="col-md-6 col-xl-4">
        <div class="card card-one">
            <div class="card-header">
                <h6 class="card-title">Recent 5 Completed Tasks</h6>
                <nav class="nav nav-icon nav-icon-sm ms-auto">
                    <a href="" class="nav-link"><i class="ri-refresh-line"></i></a>
                    <a href="" class="nav-link"><i class="ri-more-2-fill"></i></a>
                </nav>
            </div><!-- card-header -->
            <div class="card-body p-0">
                <ul class="people-group">
                    @foreach ($completedfivetasks as $task)
                        <li class="people-item">
                            <div class="avatar"><span class="avatar-initial bg-teal fs-20"></span></div>
                            <div class="people-body">
                                <h6><a href="{{ route('freelancer.singlejob', $task->id) }}">{{ str_limit(strip_tags($task->title), 30) }}
                                        @if (strlen(strip_tags($task->title)) > 30)
                                            ....
                                        @endif
                                    </a></h6>
                                <span>Task ID - {{ $task->job_id }}</span>
                            </div>
                            <div class="text-end">
                                <div class="fs-sm"> {{ $task->payment_category }} - $ {{ $task->pay_rate }}</div>
                                <span class="d-block fs-xs text-success">{{ $task->status }}</span>
                            </div>
                        </li>
                    @endforeach


                </ul>
            </div>
            <div class="card-footer d-flex justify-content-center">
                <a href="{{ route('freelancer.completejobs') }}" class="fs-sm">See My Tasks</a>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-4">
        <div class="card card-one">
            <div class="card-header">
                <h6 class="card-title">Recent Payments</h6>
                <nav class="nav nav-icon nav-icon-sm ms-auto">
                    <a href="" class="nav-link"><i class="ri-refresh-line"></i></a>
                    <a href="" class="nav-link"><i class="ri-more-2-fill"></i></a>
                </nav>
            </div><!-- card-header -->
            <div class="card-body p-0">
                <ul class="people-group">
                    <li class="people-item">
                        <div class="avatar"><span class="avatar-initial bg-teal fs-20"><i
                                    class="ri-shopping-cart-line"></i></span></div>
                        <div class="people-body">
                            <h6><a href="">Task #10322</a></h6>
                            <span>Oct 21, 2023, 3:30pm</span>
                        </div><!-- people-body -->
                        <div class="text-end">
                            <div class="fs-sm">+ $250.00</div>
                            <span class="d-block fs-xs text-success">Completed</span>
                        </div>
                    </li>

                </ul>
            </div><!-- card-body -->
            <div class="card-footer d-flex justify-content-center">
                <a href="" class="fs-sm">See All Payments</a>
            </div><!-- card-footer -->
        </div><!-- card -->
    </div><!-- col -->

    <div class="col-xl-12">
        <div class="card card-one">
            <div class="card-header">
                <h6 class="card-title">Most Recent Earnings</h6>
                <nav class="nav nav-icon nav-icon-sm ms-auto">
                    <a href="" class="nav-link"><i class="ri-refresh-line"></i></a>
                    <a href="" class="nav-link"><i class="ri-more-2-fill"></i></a>
                </nav>
            </div><!-- card-header -->
            <div class="card-body p-3 p-xl-4">
                <div class="row g-3 mb-4">
                    <div class="col">
                        <div class="earning-item">
                            <div class="earning-icon bg-primary"><i class="ri-medal-2-line"></i></div>
                            <h4><span>$</span>8890</h4>
                            <label>Total Paid</label>
                        </div><!-- earning-item -->
                    </div><!-- col -->
                    <div class="col">
                        <div class="earning-item">
                            <div class="earning-icon bg-twitter"><i class="ri-pie-chart-line"></i></div>
                            <h4><span>$</span>2340</h4>
                            <label>Pending Payments</label>
                        </div><!-- earning-item -->
                    </div><!-- col -->
                    <div class="col">
                        <div class="earning-item">
                            <div class="earning-icon bg-success"><i class="ri-line-chart-fill"></i></div>
                            <h4><span>$</span>1,60</h4>
                            <label>Disputed Payments</label>
                        </div><!-- earning-item -->
                    </div><!-- col -->
                </div><!-- row -->

                <div class="table-responsive mb-4">
                    <table class="table table-two mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Task ID</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>03/05/2023</td>
                                <td>1,050</td>
                                <td class="text-success">+ $32,580.00</td>
                                <td class="text-danger">- $3,023.10</td>
                                <td>4.5%</td>
                            </tr>
                            <tr>
                                <td>03/04/2023</td>
                                <td>980</td>
                                <td class="text-success">+ $30,065.10</td>
                                <td class="text-danger">- $2,780.00</td>
                                <td>3.8%</td>
                            </tr>
                            <tr>
                                <td>03/04/2023</td>
                                <td>954</td>
                                <td class="text-success">+ $28,994.00</td>
                                <td class="text-danger">- $2,540.60</td>
                                <td>3.3%</td>
                            </tr>
                            <tr>
                                <td>03/02/2023</td>
                                <td>792</td>
                                <td class="text-success">+ $25,300.90</td>
                                <td class="text-danger">- $2,144.60</td>
                                <td>2.9%</td>
                            </tr>
                            <tr>
                                <td>02/28/2023</td>
                                <td>788</td>
                                <td class="text-success">+ $24,887.08</td>
                                <td class="text-danger">- $1,980.00</td>
                                <td>2.6%</td>
                            </tr>
                        </tbody>
                    </table>
                </div><!-- table-responsive -->

            </div><!-- card-body -->
        </div><!-- card -->
    </div><!-- col -->
@endsection
