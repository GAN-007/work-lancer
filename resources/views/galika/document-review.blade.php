@extends('galika.layout')
@section('title','Review CV Evidence')
@section('content')
<h2>Confirm CV Evidence</h2>
<p class="text-muted">Only checked facts become verified evidence GALIKA may use in applications.</p>
<form method="post" action="{{ route('galika.documents.confirm',$document) }}">@csrf
<div class="card p-4">
@forelse($document->extracted_facts ?? [] as $i=>$fact)
<div class="form-check border-bottom py-3">
<input class="form-check-input" type="checkbox" name="accepted[]" value="{{ $i }}" id="f{{ $i }}">
<label class="form-check-label" for="f{{ $i }}"><strong>{{ $fact['domain'] ?? 'General' }}</strong><br>{{ $fact['fact'] ?? '' }}<br><small class="text-muted">confidence {{ $fact['confidence'] ?? '—' }}</small></label>
</div>
@empty
<p>No facts were extracted.</p>
@endforelse
<button class="btn btn-primary mt-3">Confirm selected evidence</button>
</div></form>
@endsection