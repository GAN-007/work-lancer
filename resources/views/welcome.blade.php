<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/welcome/favicon.png') }}" />

    <!-- Plugin CSS -->
    <link rel="stylesheet" href="{{ asset('assets/welcome/jquery-ui/jquery-ui.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/welcome/swiper/swiper-bundle.min.css') }}" />

    <!-- Theme CSS -->
    <link rel="stylesheet" href="{{ asset('assets/welcome/dist/css/style.css') }}" />

    <title>StudyProfessors</title>

    <!-- Fonts -->
    <link href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="{{ asset('assets/welcome/dist/css/main.css') }}">
</head>



<body class="page__joblist">
    <nav class="navbar navbar-expand-xl">
        <div class="container-xxl justify-content-md-start">
            <a class="navbar-brand" href="{{ url('/') }}">
                <img src="{{ asset('assets/prof.png') }}" style="height: 120px; width: 200px;" alt="" />
            </a>
            <!-- /.navbar-brand -->

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                <img src="{{ asset('assets/icons/bars.svg') }}" class="svg-inject svg-icon" alt="" />
            </button>
            <!-- /.navbar-toggler-mobile -->

            <div class="d-none d-md-flex d-xl-none ms-auto">
                <a class="btn btn-alice-blue text-dark fw-semiBold py-12 px-34 me-12 rounded-pill"
                    href="{{ route('login') }}" role="button">Log In</a>
                <a class="btn btn-primary fw-semiBold py-12 px-43 rounded-pill" href="{{ route('register') }}"
                    role="button">Register</a>

            </div>
            <!-- /.btn-login and btn-signup mobile -->

            <div class="collapse navbar-collapse" id="navbarContent">
                <div class="d-grid d-xl-flex align-items-xl-center pb-15 pb-xl-0 w-100 gap-20 gap-xl-0">
                    <ul class="navbar-nav mx-xl-auto order-2 order-xl-1">
                        <li class="nav-item">
                            <a class="nav-link active" href="{{ url('/') }}">Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="">All Jobs</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="">Job Categories</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" aria-current="page" href="{{ route('login') }}">Post New Job</a>
                        </li>
                    </ul>
                    <!-- /.navbar-nav -->

                    <form class="order-1 order-xl-2" role="search" action="#" method="get">
                        <div class="form-search">
                            <input class="form-control border border-alice-blue" type="search"
                                placeholder="Search here..." aria-label="Search" />
                        </div>
                    </form>
                    <!-- /.form-search -->

                    <div class="d-grid d-md-none d-xl-flex gap-15 gap-xl-0 order-3">
                        <a class="btn btn-alice-blue text-dark fw-semiBold py-12 px-xl-34 ms-xl-12 rounded-pill"
                            href="{{ route('login') }}" role="button">Log In</a>
                        <a class="btn btn-primary fw-semiBold py-12 px-xl-43 ms-xl-12 rounded-pill"
                            href="{{ route('register') }}" role="button">Sign Up</a>
                    </div>
                    <!-- /.btn-login and btn-sign-up desktop -->
                </div>
            </div>
        </div>
    </nav>
    <!-- = /. Navbar Section = -->

    <main class="main">
        <div class="container-xxl">
            <div class="row">
                <div class="col-12 col-lg-6 my-34 my-lg-80 my-xl-120 my-xxl-180 order-2 order-lg-1">
                    <h1 class="heading-header-1 mb-0">Find your dream jobs through <span class="text-primary">StudyProfessors</span> Easily</h1>
                    <!-- /.heading-header -->

                    <form class="ms-auto mt-60" role="search" action="#" method="get">
                        <div class="form-btn-inner shadow-2 rounded-pill">
                            <input class="form-control" type="search" placeholder="Search here..." />
                            <button class="btn btn-primary fw-semiBold py-12 px-20 px-lg-34 flex-nofill shadow-none"
                                type="submit">Search Job</button>
                        </div>
                    </form>
                    <!-- /.search-job-form -->

                    <div class="d-flex flex-wrap justify-content-center justify-content-lg-start mt-34 gap-12">
                        @php
                            $categories = App\Models\JobCategory::all();
                            $jobs = App\Models\Job::where('status', 'active')
                                ->orderBy('updated_at', 'desc')
                                ->get();
                        @endphp
                        @foreach ($categories as $item)
                            <a href="#"
                                class="btn btn-suggestion fw-medium rounded-pill shadow-none">{{ ucwords($item->category_name) }}</a>
                        @endforeach

                    </div>
                    <!-- /.search-suggestion -->
                </div>
                <!-- /.col -->

                <div class="col-12 col-lg-6 order-1 order-lg-2">
                    <div class="position-relative h-lg-100">
                        <img src="{{ asset('assets/welcome/pages/joblist/header/heroImage.png') }}"
                            class="hero-image-img" alt="" />
                    </div>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container -->
        <!-- = /. Header Section = -->

        <div class="mt-43"></div>
        <!-- = /. Gap Section = -->

        <div class="container-xxl">
            <div class="d-flex flex-column flex-xl-row align-items-xl-center gap-20">
                <h4 class="heading-section-4 text-dark mb-0">Showing {{ $jobs->count() }} Jobs</h4>
                <!-- /.heading-section -->


            </div>

            <div class="d-grid mt-60 gap-34">
                @forelse ($jobs as $item)
                    <div class="job-card-harizontal">
                        <button
                            class="btn btn-rounded btn-white position-absolute d-xl-none top-100 start-50 p-10 translate-middle"
                            type="button" data-bs-toggle="collapse" data-bs-target="#jobInfoCardCollapse"
                            aria-expanded="false" aria-controls="jobInfoCardCollapse">
                            <img src="https://ui-avatars.com/api/?name={{ $item->jobemployer->name }}"
                                class="svg-inject text-primary is-16" alt="" />
                        </button>
                        <div class="job-info">
                            <div class="job-image">
                                <img src="https://ui-avatars.com/api/?name={{ $item->jobemployer->name }}" alt="" />
                            </div>
                            <div class="job-info-inner">
                                <h5 class="job-title">
                                    <a href="{{ url('view-task/'.$item->job_id) }}">Task ID - {{ $item->job_id }}</a>
                                </h5>
                                <div class="job-author">
                                    <a href="{{ url('view-task/'.$item->job_id) }}"></a>
                                    {{ str_limit(strip_tags($item->title), 50) }}
                                    @if (strlen(strip_tags($item->title)) > 50)
                                        ....
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="job-info-card-collapse collapse" id="jobInfoCardCollapse">
                            <div class="job-info-card">
                                <div class="info-card">
                                    <div class="info-card-icon bg-ufo-green-100 rounded-pill">
                                        <img src="{{ asset('assets/icons/sack-dollar.svg') }}"
                                            class="svg-inject svg-icon" alt="" />
                                    </div>
                                    <div class="info-card-content">
                                        <h6 class="info-card-title">${{ $item->pay_rate }}</h6>
                                        <div class="info-card-desc">{{ $item->payment_category }}</div>
                                    </div>
                                </div>
                                <div class="info-card">
                                    <div class="info-card-icon bg-rajah rounded-pill">
                                        <img src="{{ asset('assets/icons/mark.svg') }}" class="svg-inject svg-icon"
                                            alt="" />
                                    </div>
                                    @php

                                        $attachments  = App\Models\JobAttachment::where(['category'=>'attachment', 'job_id'=>$item->id])->count();
                                    @endphp
                                    <div class="info-card-content">
                                        <h6 class="info-card-title">{{ $attachments}}</h6>
                                        <div class="info-card-desc">Files Attached</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="job-action">
                            @if (!Auth::user())
                                <a class="btn btn-apply flex-fill flex-md-nofill fw-semiBold py-12 px-43 rounded-pill"
                                    href="{{ route('login') }}">Login To Apply </a>
                            @else
                                <a class="btn btn-apply flex-fill flex-md-nofill fw-semiBold py-12 px-43 rounded-pill"
                                    href="jobdetail.html">Apply Now</a>
                            @endif
                        </div>
                    </div>
                @empty
                @endforelse

                <!-- /.job-card-grid-harizontal-component -->


            </div>
            <!-- /.job-list -->


        </div>
        <!-- /.container -->
        <!-- = /. Job List Section = -->


        <div class="mt-43"></div>

    </main>
    <!-- = /. Main Section = -->

    <footer class="footer bg-black">
        <div class="container-xxl">
            <div class="row py-25 py-lg-43 py-xl-80 align-items-md-center">
                <div class="col-12 col-md-6">
                    <h2 class="heading-section-2 text-white mb-0">Don't miss our weekly updates about freelancer career
                    </h2>
                    <!-- /.newsletter-heading -->
                </div>
                <!-- /.col -->

                <div class="col-12 col-md-6 col-lg-5 offset-lg-1 mt-25 mt-md-0">
                    <form class="ms-auto" action="#">
                        <div class="form-btn-inner form-newsletter rounded-pill">
                            <input class="form-control" type="email" placeholder="Enter your email address..." />
                            <button
                                class="btn btn-accent text-white text-uppercase fw-semiBold py-12 px-20 px-lg-34 shadow-none"
                                type="submit">Subscribe</button>
                        </div>
                    </form>
                    <!-- /.newsletter-form -->
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->

            <div class="row py-20 py-xl-80">
                <div class="col-12 col-xl-4 footer-widget about">
                    <div class="footer-brand">
                        <img src="{{ asset('assets/prof.pngb') }}" alt="logo" />
                    </div>
                    <p class="footer-description mt-34">StudyProfessors is an freelancing platform headquartered in Nairobi,
                        Kenya. The company was formed in 2023 after the merger of Company A. and Company B.</p>
                    <ul class="social-btn-group mt-34">
                        <li class="social-item">
                            <a class="social-link" href="#">
                                <img src="{{ asset('assets/icons/facebook-f.svg') }}" class="svg-inject svg-icon"
                                    alt="" />
                            </a>
                        </li>
                        <li class="social-item">
                            <a class="social-link" href="#">
                                <img src="{{ asset('assets/icons/twitter.svg') }}" class="svg-inject svg-icon"
                                    alt="" />
                            </a>
                        </li>
                        <li class="social-item">
                            <a class="social-link" href="#">
                                <img src="{{ asset('assets/icons/youtube.svg') }}" class="svg-inject svg-icon"
                                    alt="" />
                            </a>
                        </li>
                        <li class="social-item">
                            <a class="social-link" href="#">
                                <img src="{{ asset('assets/icons/linkedin.svg') }}" class="svg-inject svg-icon"
                                    alt="" />
                            </a>
                        </li>
                        <li class="social-item">
                            <a class="social-link" href="#">
                                <img src="{{ asset('assets/icons/instagram.svg') }}" class="svg-inject svg-icon"
                                    alt="" />
                            </a>
                        </li>
                    </ul>
                    <!-- /.social-btn-component -->
                </div>
                <!-- /.col -->

                <div class="col-12 col-lg-4 col-xl footer-widget link-list mt-34 mt-xl-0">
                    <h6 class="footer-title">StudyProfessors</h6>
                    <ul class="footer-link-list">
                        <li class="link-item">
                            <a class="link" href="#">Why Us</a>
                        </li>
                        <li class="link-item">
                            <a class="link" href="#">Freelancer Account</a>
                        </li>
                        <li class="link-item">
                            <a class="link" href="#">Employer Account</a>
                        </li>

                    </ul>
                </div>
                <!-- /.col -->

                <div class="col-12 col-lg-4 col-xl footer-widget link-list mt-34 mt-xl-0">
                    <h6 class="footer-title">Resources</h6>
                    <ul class="footer-link-list">
                        <li class="link-item">
                            <a class="link" href="#">All Categories</a>
                        </li>
                        <li class="link-item">
                            <a class="link" href="#">All Jobs</a>
                        </li>
                        <li class="link-item">
                            <a class="link" href="#">My Account</a>
                        </li>

                    </ul>
                </div>
                <!-- /.col -->

                <div class="col-12 col-lg-4 col-xl footer-widget link-list mt-34 mt-xl-0">
                    <h6 class="footer-title">Company</h6>
                    <ul class="footer-link-list">
                        <li class="link-item">
                            <a class="link" href="#">About us</a>
                        </li>
                        <li class="link-item">
                            <a class="link" href="#">Contact us</a>
                        </li>
                        <li class="link-item">
                            <a class="link" href="#">Jobs</a>
                        </li>

                    </ul>
                </div>
                <!-- /.col -->

                <div class="col-12 col-xl-3 footer-widget link-list _icon mt-34 mt-xl-0">
                    <h6 class="footer-title">Get in Touch with Us</h6>
                    <ul class="footer-link-list _icon">
                        <li class="link-item">
                            <img src="{{ asset('assets/icons/mark-stroke.svg') }}" class="svg-inject svg-icon"
                                alt="" />
                            <a class="link" href="#">
                                <span>832 Lumumba Drive, San Road 94107, Nairobi</span>
                            </a>
                        </li>
                        <li class="link-item">
                            <img src="{{ asset('assets/icons/phone-stroke.svg') }}" class="svg-inject svg-icon"
                                alt="" />
                            <a class="link" href="#">
                                <span>+254718052608</span>
                            </a>
                        </li>
                        <li class="link-item">
                            <img src="{{ asset('assets/icons/envelope-stroke.svg') }}" class="svg-inject svg-icon"
                                alt="" />
                            <a class="link" href="#">
                                <span>johnmambo794@gmail.com</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <!-- /.col -->
            </div>
            <!-- /.row -->
        </div>
        <!-- /.container -->

        <div class="border border-arsenic border-end-0 border-bottom-0 border-start-0">
            <div class="container-xxl d-grid d-lg-flex gap-12 gap-xl-0 justify-content-lg-between py-25">
                <div class="fs-14 text-white text-center">StudyProfessors - © 2023 All Rights Reserved
                </div>

                <div class="fs-14 text-white text-center">Made with ♥ by John Mambo</div>
            </div>
        </div>
    </footer>
    <!-- = /. Footer Section = -->

    <!-- Bootstrap JS -->
    <script src="{{ asset('assets/welcome/vendors/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Vendors -->
    <script src="{{ asset('assets/welcome/vendors/jquery/jquery-3.6.0.min.js') }}"></script>
    <script src="{{ asset('assets/welcome/vendors/jquery-ui/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/welcome/vendors/swiper/swiper-bundle.min.js') }}"></script>
    <script src="{{ asset('assets/welcome/vendors/fslightbox/fslightbox.js') }}"></script>
    <script src="{{ asset('assets/welcome/vendors/svg-inject/dist/svg-inject.min.js') }}"></script>

    <!-- Template Function -->
    <script src="{{ asset('assets/welcome/dist/js/theme.js') }}"></script>
</body>

</html>
