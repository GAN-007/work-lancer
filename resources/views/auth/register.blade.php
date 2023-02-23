<html lang="en">

<head>

    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Meta -->
    <meta name="description" content="">
    <meta name="author" content="Work Palace">

    <title>Freelancer Register</title>

    <link rel="stylesheet" href="{{ asset('assets/admin/lib/remixicon/fonts/remixicon.css') }}">

    <!-- Template CSS -->
    <link rel="stylesheet" href="{{ asset('assets/admin/assets/css/style.min.css') }}">
</head>

<body class="page-sign mt-0">

    <div class="card card-sign ">
        <div class="card-header text-center">
            <a href="{{ url('/') }}" class="header-logo mb-2 ">StudyProfessors</a>
            <h3 class="card-title">Sign Up</h3>
        </div><!-- card-header -->
        <div class="card-body">
            <form method="POST" action="{{ route('createFreelancer') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Name</label>
                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror"
                        name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                    @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Email address</label>
                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                        name="email" value="{{ old('email') }}" required autocomplete="email">

                    @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                    @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password">
                </div>

                <div class="mb-4">
                    <small>By clicking <strong>Create Account</strong> below, you agree to our terms of service and
                        privacy
                        statement.</small>
                </div>
                <button class="btn btn-primary btn-sign" type="submit">Create Account</button>
                <div class="card-footer">
                    Already have an account? <a href="{{ route('login') }}">Sign In</a> or 
                    Create an Account as <a href="{{ url('employer_account') }}">Employer</a>
                </div>
            </form>

        </div><!-- card-body -->
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
