@extends('galika.layout')
@section('title','GALIKA Connections')
@section('content')
<h2>Connect your accounts</h2>
<p class="text-muted">For supported providers, sign in on the provider's own page and authorize GALIKA. You do not need to copy access tokens manually.</p>

@php($oauthProviders=['gmail','airtable','linkedin','lever'])
@php($keyProviders=['openai','tinyfish'])

<div class="row g-3 mb-4">
@foreach($oauthProviders as $provider)
@php($c=$connections->firstWhere('provider',$provider))
<div class="col-md-6 col-xl-3">
  <div class="card p-3 h-100">
    <h5 class="text-capitalize">{{ $provider }}</h5>
    <div class="mb-2">
      <span class="badge {{ $c ? 'bg-success' : 'bg-secondary' }}">{{ $c?->health ?? 'NOT CONNECTED' }}</span>
    </div>
    <p class="small text-muted mb-3">
      @if($provider==='gmail')Mail discovery, submission evidence, recruiter replies and follow-up.
      @elseif($provider==='airtable')Operational sync and GALIKA control-plane data.
      @elseif($provider==='linkedin')User-authorized LinkedIn identity/data access where granted scopes permit.
      @elseif($provider==='lever')OAuth-backed Lever access where the connected account and scopes permit.
      @endif
    </p>
    <a class="btn btn-primary" href="{{ route('galika.oauth.start',$provider) }}">{{ $c ? 'Reconnect' : 'Connect' }} {{ ucfirst($provider) }}</a>
    @if($c)
      <form method="post" action="{{ route('galika.connections.test',$provider) }}" class="mt-2">@csrf
        <button class="btn btn-outline-secondary btn-sm">Run health test</button>
      </form>
    @endif
  </div>
</div>
@endforeach
</div>

<h4>Service credentials</h4>
<p class="text-muted">These providers use service/API credentials rather than end-user OAuth in this deployment.</p>
<div class="row g-3">
@foreach($keyProviders as $provider)
@php($c=$connections->firstWhere('provider',$provider))
<div class="col-md-6 col-xl-4">
  <div class="card p-3 h-100">
    <h5 class="text-capitalize">{{ $provider }}</h5>
    <div class="mb-2"><span class="badge bg-secondary">{{ $c?->health ?? 'NOT CONFIGURED' }}</span></div>
    <form method="post" action="{{ route('galika.connections.api') }}">@csrf
      <input type="hidden" name="provider" value="{{ $provider }}">
      <input class="form-control mb-2" type="password" name="api_key" placeholder="{{ ucfirst($provider) }} API key" required>
      <button class="btn btn-primary">Save securely</button>
    </form>
    @if($c)
      <form method="post" action="{{ route('galika.connections.test',$provider) }}" class="mt-2">@csrf
        <button class="btn btn-outline-secondary btn-sm">Run health test</button>
      </form>
    @endif
  </div>
</div>
@endforeach
</div>
@endsection