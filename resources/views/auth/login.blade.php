<html lang="en">

<head>

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Meta -->
    <meta name="description" content="">
    <meta name="author" content="Work Palace">

      <title>Work Palace Login</title>

    <link rel="stylesheet" href="{{ asset('assets/admin/lib/remixicon/fonts/remixicon.css') }}">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('assets/admin/assets/css/style.min.css') }}">
</head>

<body class="page-sign">

    <div class="card card-sign">
        <div class="card-header text-center">
            <a href="{{ url('/') }}" class="header-logo mb-4">StudyProfessors</a>
            <h3 class="card-title ">Sign In</h3>
        </div><!-- card-header -->
        <div class="card-body">
        @if (session()->has('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif
            <form method="POST" class="form-horizontal " action="{{ route('login') }}">
                @csrf
                <div class="mb-4">
                    <label class="form-label">Email address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                        name="email" value="{{ old('email') }}" required autocomplete="email" autofocus
                        placeholder="Email Address">

                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror

                </div>
                <div class="mb-4">
                    <label class="form-label d-flex justify-content-between">Password <a href="">Forgot
                            password?</a>
                    </label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                        name="password" required autocomplete="current-password">

                    @error('password')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-4">
                    <div class="d-flex custom-checkbox">
                        <input class="form-check-input" type="checkbox" name="remember" id="customCheck1"
                            {{ old('remember') ? 'checked' : '' }}>

                        <label class="form-label ms-1" for="customCheck1">
                            {{ __('Remember Me') }}
                        </label>
                    </div>
                </div>
                <button class="btn btn-primary btn-sign" type="submit">Sign In</button>
            </form>



        </div>
        <div class="card-footer">
            Don't have an account? Create an Account as <a href="{{ route('register') }}">Freelancer</a> or
            <a href="{{ url('employer_account') }}">Employer</a>
        </div>
        <!-- card-footer -->
    </div><!-- card -->

    <script src="{{ asset('assets/admin/lib/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/admin/lib/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        'use script'

        var skinMode = localStorage.getItem('skin-mode');
        if (skinMode) {
            $('html').attr('data-skin', 'dark');
        }
    </script>


</body>

</html>
