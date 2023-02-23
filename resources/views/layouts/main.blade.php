<!DOCTYPE html>
<html lang="en">

<head>

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Meta -->
    <meta name="description" content="">
    <meta name="author" content="StudyProfessors">

    <title>@yield('title')</title>

    <!-- Vendor CSS -->
    <link rel="stylesheet" href="{{ asset('assets/admin/lib/remixicon/fonts/remixicon.css') }}">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('assets/admin/assets/css/style.min.css') }}">
    <link rel="stylesheet" href="http://cdn.bootcss.com/toastr.js/latest/css/toastr.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.2/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.4/css/buttons.dataTables.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>


    @livewireStyles

</head>

<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <a href="#" class="sidebar-logo">StudyProfessors</a>
        </div><!-- sidebar-header -->
        <div id="sidebarMenu" class="sidebar-body">
            <div class="nav-group show">
                {{-- <a href="#" class="nav-label">Dashboard</a> --}}
                <ul class="nav nav-sidebar">
                    @role('user')
                        <li class="nav-item">
                            <a href="{{ route('user.dashboard') }}" class="nav-link"><i class="ri-folder-2-line"></i>
                                <span>Dashboard</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('user.new-job') }}" class="nav-link"><i class="ri-mail-send-line"></i>
                                <span>Post
                                    Job</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('user.alljobs') }}" class="nav-link"><i class="ri-calendar-line"></i>
                                <span>All Jobs
                                </span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('user.draftjobs') }}" class="nav-link"><i class="ri-question-answer-line"></i>
                                <span>Draft Jobs</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('user.completejobs')}}" class="nav-link"><i class="ri-contacts-book-line"></i>
                                <span>Completed Jobs</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('user.jobsinprogress')}}" class="nav-link"><i class="ri-checkbox-multiple-line"></i> <span>In Progress
                                    Tasks
                                </span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('user.allpayments')}}" class="nav-link"><i class="ri-checkbox-multiple-line"></i> <span>All
                                    Payments</span></a>
                        </li>
                    @endrole
                    @role('superadmin')
                        <li class="nav-item">
                            <a href="{{ route('superadmin.dashboard') }}" class="nav-link"><i class="ri-folder-2-line"></i>
                                <span>Dashboard</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.create.category') }}" class="nav-link"><i
                                    class="ri-mail-send-line"></i>
                                <span>Add Job category</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.alljobcategories') }}" class="nav-link"><i
                                    class="ri-calendar-line"></i>
                                <span>All Job Categories
                                </span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.alljobs') }}" class="nav-link"><i
                                    class="ri-question-answer-line"></i>
                                <span>All Jobs</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.allcompletejobs') }}" class="nav-link"><i
                                    class="ri-contacts-book-line"></i>
                                <span>Completed Jobs</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.jobsinprogress') }}" class="nav-link"><i
                                    class="ri-checkbox-multiple-line"></i> <span>In Progress
                                    Tasks
                                </span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.jobsasdrafts') }}" class="nav-link"><i
                                    class="ri-checkbox-multiple-line"></i> <span>Draft Tasks
                                </span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.allpayments') }}" class="nav-link"><i
                                    class="ri-checkbox-multiple-line"></i> <span>All
                                    Payments</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.all-employers') }}" class="nav-link"><i
                                    class="ri-checkbox-multiple-line"></i> <span>All
                                    Employers</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('superadmin.all-freelancers')}}" class="nav-link"><i class="ri-checkbox-multiple-line"></i> <span>All
                                    Freelancers</span></a>
                        </li>
                    @endrole
                    @role('freelancer')
                        <li class="nav-item">
                            <a href="" class="nav-link"><i class="ri-folder-2-line"></i> <span>Dashboard</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('freelancer.postedjobs') }}" class="nav-link"><i
                                    class="ri-mail-send-line"></i> <span>Posted
                                    Jobs</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('freelancer.completejobs') }}" class="nav-link"><i
                                    class="ri-calendar-line"></i> <span>Completed
                                    Tasks</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('freelancer.pendingjobs') }}" class="nav-link"><i
                                    class="ri-question-answer-line"></i>
                                <span>Pending Tasks</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('freelancer.allpayments') }}" class="nav-link"><i
                                    class="ri-contacts-book-line"></i>
                                <span>All Payments</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('freelancer.completepayments') }}" class="nav-link"><i
                                    class="ri-checkbox-multiple-line"></i> <span>Completed
                                    Payments</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('freelancer.pendingpayments') }}" class="nav-link"><i
                                    class="ri-checkbox-multiple-line"></i> <span>Pending
                                    Payments</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('freelancer.disputedpayments') }}" class="nav-link"><i
                                    class="ri-checkbox-multiple-line"></i> <span>Disputed
                                    Payments</span></a>
                        </li>
                    @endrole
                </ul>
            </div><!-- sidebar-body -->

        </div><!-- sidebar -->
    </div>
    <div class="header-main px-3 px-lg-4">
        <a id="menuSidebar" href="#" class="menu-link me-3 me-lg-4"><i class="ri-menu-2-fill"></i></a>

        <div class="form-search me-auto">
            <input type="text" class="form-control" placeholder="Search">
            <i class="ri-search-line"></i>
        </div><!-- form-search -->

        <div class="dropdown dropdown-skin">
            <a href="#" class="dropdown-link" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i
                    class="ri-settings-3-line"></i></a>
            <div class="dropdown-menu dropdown-menu-end mt-10-f">
                <label>Skin Mode</label>
                <nav id="skinMode" class="nav nav-skin">
                    <a href="#" class="nav-link active">Light</a>
                    <a href="#" class="nav-link">Dark</a>
                </nav>
                <hr>
                <label>Sidebar Skin</label>
                <nav id="sidebarSkin" class="nav nav-skin">
                    <a href="#" class="nav-link active">Default</a>
                    <a href="#" class="nav-link">Prime</a>
                    <a href="#" class="nav-link">Dark</a>
                </nav>
            </div><!-- dropdown-menu -->
        </div><!-- dropdown -->

        <div class="dropdown dropdown-notification ms-3 ms-xl-4">
            <a href="#" class="dropdown-link" data-bs-toggle="dropdown"
                data-bs-auto-close="outside"><small>1</small><i class="ri-notification-3-line"></i></a>
            <div class="dropdown-menu dropdown-menu-end mt-10-f me--10-f">
                <div class="dropdown-menu-header">
                    <h6 class="dropdown-menu-title">Notifications</h6>
                </div><!-- dropdown-menu-header -->
                <ul class="list-group">
                    <li class="list-group-item">
                        <div class="avatar online"><img src="" alt="">
                        </div>
                        <div class="list-group-body">
                            <p><strong>Dominador Manuel</strong> and <strong>100 other people</strong> reacted to
                                your
                                comment "Tell
                                your partner that...</p>
                            <span>Aug 20 08:55am</span>
                        </div><!-- list-group-body -->
                    </li>

                </ul>
                <div class="dropdown-menu-footer"><a href="#">Show all Notifications</a></div>
            </div><!-- dropdown-menu -->
        </div><!-- dropdown -->
        <div class="dropdown dropdown-profile ms-3 ms-xl-4">
            <a href="#" class="dropdown-link" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                <div class="avatar online"><img src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}"
                        alt=""></div>
            </a>
            <div class="dropdown-menu dropdown-menu-end mt-10-f">
                <div class="dropdown-menu-body">
                    <div class="avatar avatar-xl online mb-3"><img
                            src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}" alt="">
                    </div>
                    <h5 class="mb-1 text-dark fw-semibold">{{ Auth::user()->name }}</h5>

                    <nav class="nav">
                        <a href="#"><i class="ri-profile-line"></i> View Profile</a>
                    </nav>
                    <hr>
                    <nav class="nav">
                        <a href="#"><i class="ri-user-settings-line"></i> Account Settings</a>
                        <a href="{{ route('logout') }}"
                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                            {{ __('Logout') }} <i class="ri-logout-box-r-line"></i>
                        </a>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </nav>
                </div><!-- dropdown-menu-body -->
            </div><!-- dropdown-menu -->
        </div><!-- dropdown -->
    </div><!-- header-main -->

    <div class="main main-app p-3 p-lg-4">


        <div class="row g-3" style="min-height: 80vh">
            @yield('content')
        </div><!-- row -->

        <div class="main-footer mt-5">
            <span>&copy; 2023. All Rights Reserved.</span>
        </div><!-- main-footer -->
    </div><!-- main -->


    <script src="{{ asset('assets/admin/lib/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/perfect-scrollbar/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/jquery.flot/jquery.flot.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/jquery.flot/jquery.flot.stack.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/jquery.flot/jquery.flot.resize.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/chart.js/chart.min.js') }}"></script>

    <script src="{{ asset('assets/admin/assets/js/script.js') }}"></script>
    <script src="{{ asset('assets/admin/assets/js/db.product.js') }}"></script>
    @livewireScripts
    <script src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="https://cdn.datatables.net/1.13.2/js/jquery.dataTables.min.js
                                "></script>
    <script src=" https://cdn.datatables.net/buttons/2.3.4/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.4/js/buttons.print.min.js"></script>


    <script>
        $(document).ready(function() {
            $('#example').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'print'
                ]
            });
        });
    </script>


</body>


</html>
