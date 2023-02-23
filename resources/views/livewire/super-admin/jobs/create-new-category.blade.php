<div>
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-body">
                    @if (session()->has('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    <h4 class="card-title text-center">Add Category</h4>
                    <form class="needs-validation" method="POST" action="{{ route('superadmin.createJobCategory') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <label for="validationCustom01" class="form-label">Category Name</label>
                                    <input id="validationCustom01" type="text"
                                        class="form-control @error('category_name') is-invalid @enderror" name="category_name"
                                        value="{{ old('category_name') }}" required autocomplete="category_name" autofocus>

                                    @error('category_name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div>
                            <button class="btn btn-primary" type="submit">Save</button>
                        </div>
                    </form>
                </div>
            </div>
            <!-- end card -->
        </div> <!-- end col -->


    </div>
</div>
