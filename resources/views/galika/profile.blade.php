@extends('galika.layout')
@section('title','GALIKA Career Profile')
@section('content')
<h2 class="mb-4">Career Profile & Autonomy</h2><form method="post" action="{{ route('galika.profile.save') }}" class="card p-4">@csrf
<div class="row g-3"><div class="col-md-6"><label class="form-label">Headline</label><input class="form-control" name="headline" value="{{ old('headline',$profile->headline) }}"></div>
<div class="col-md-6"><label class="form-label">Location</label><input class="form-control" name="location" value="{{ old('location',$profile->location) }}"></div>
<div class="col-md-4"><label class="form-label">Country</label><input class="form-control" name="country" value="{{ old('country',$profile->country) }}"></div>
<div class="col-md-4"><label class="form-label">Timezone</label><input class="form-control" name="timezone" value="{{ old('timezone',$profile->timezone) }}"></div>
<div class="col-md-4"><label class="form-label">Minimum match score</label><input type="number" min="0" max="100" class="form-control" name="minimum_match_score" value="{{ old('minimum_match_score',$profile->minimum_match_score) }}"></div>
<div class="col-md-4"><label class="form-label">Review mode</label><select class="form-select" name="review_mode">@foreach(['MATERIAL_ONLY','REVIEW_ALL','AUTONOMOUS'] as $m)<option @selected($profile->review_mode===$m)>{{ $m }}</option>@endforeach</select></div>
<div class="col-md-4 d-flex align-items-end"><div class="form-check"><input type="hidden" name="autonomous_apply_enabled" value="0"><input class="form-check-input" type="checkbox" name="autonomous_apply_enabled" value="1" @checked($profile->autonomous_apply_enabled)><label class="form-check-label">Enable autonomous apply</label></div></div>
<div class="col-md-4 d-flex align-items-end"><div class="form-check"><input type="hidden" name="pause_all_execution" value="0"><input class="form-check-input" type="checkbox" name="pause_all_execution" value="1" @checked($profile->pause_all_execution)><label class="form-check-label">Pause all execution</label></div></div></div>
<button class="btn btn-primary mt-4">Save profile</button></form>
<div class="card p-4 mt-4"><h5>CV / Resume</h5><form method="post" enctype="multipart/form-data" action="{{ route('galika.cv.upload') }}">@csrf<input class="form-control" type="file" name="cv" accept=".pdf,.docx,.txt" required><button class="btn btn-primary mt-2">Upload and extract evidence</button></form></div>
<div class="card p-4 mt-4"><h5>Verified evidence</h5>@forelse($evidence as $e)<div class="border-bottom py-2"><strong>{{ $e->domain }}</strong><div>{{ $e->fact }}</div><small class="text-muted">{{ $e->source_type }} · confidence {{ $e->confidence }}</small></div>@empty<p class="text-muted">No verified evidence loaded yet.</p>@endforelse</div>
@endsection