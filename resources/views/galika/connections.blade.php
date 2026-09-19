@extends('galika.layout')
@section('title','GALIKA Connections')
@section('content')
<h2>Connections & Credentials</h2>
<p class="text-muted">Connect Gmail through OAuth and store OpenAI, Airtable and TinyFish credentials encrypted per user.</p>
<div class="row g-3">
@foreach(['gmail','openai','airtable','tinyfish'] as $provider)
@php($c=$connections->firstWhere('provider',$provider))
<div class="col-md-6 col-xl-3"><div class="card p-3 h-100">
<h5 class="text-capitalize">{{ $provider }}</h5>
<div class="mb-3"><span class="badge bg-secondary">{{ $c?->health ?? 'DISCONNECTED' }}</span></div>
@if($provider==='gmail')
<a class="btn btn-primary" href="{{ route('galika.oauth.start','gmail') }}">Connect Gmail</a>
@else
<form method="post" action="{{ route('galika.connections.api') }}">@csrf
<input type="hidden" name="provider" value="{{ $provider }}">
<input class="form-control mb-2" type="password" name="api_key" placeholder="API key" required>
<button class="btn btn-primary">Save securely</button>
</form>
@endif
@if($c)
<form method="post" action="{{ route('galika.connections.test',$provider) }}" class="mt-2">@csrf<button class="btn btn-outline-secondary btn-sm">Run health test</button></form>
@endif
</div></div>
@endforeach
</div>
@endsection