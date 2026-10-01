@extends('galika.layout')
@section('title','GALIKA Connections')
@section('content')
<h2>Connections & local execution</h2>
<p class="text-muted">User-authorized providers remain user-scoped. Browser execution and operational projection are now self-hosted system services, so GALIKA no longer requires TinyFish or Airtable credentials.</p>

@php($oauthProviders=['gmail','linkedin','lever'])
@php($keyProviders=['openai'])

<div class="row g-3 mb-4">
@foreach($oauthProviders as $provider)
@php($c=$connections->firstWhere('provider',$provider))
<div class="col-md-6 col-xl-4">
  <div class="card p-3 h-100">
    <h5 class="text-capitalize">{{ $provider }}</h5>
    <div class="mb-2">
      <span class="badge {{ $c ? 'bg-success' : 'bg-secondary' }}">{{ $c?->health ?? 'NOT CONNECTED' }}</span>
    </div>
    <p class="small text-muted mb-3">
      @if($provider==='gmail')Mail discovery, submission evidence, recruiter replies and follow-up.
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

<h4>Self-hosted system services</h4>
<p class="text-muted">These services are configured by deployment environment variables and do not consume per-user credits.</p>
<div class="row g-3 mb-4">
@foreach($systemServices as $provider=>$service)
<div class="col-md-6">
  <div class="card p-3 h-100">
    <h5>{{ $provider==='open_web_agent' ? 'Open Web Agent' : 'Baserow' }}</h5>
    <div class="mb-2">
      <span class="badge {{ $service['configured'] ? 'bg-success' : 'bg-secondary' }}">{{ $service['configured'] ? 'CONFIGURED' : 'OPTIONAL / NOT CONFIGURED' }}</span>
    </div>
    <p class="small text-muted">{{ $service['description'] }}</p>
    @if($service['configured'])
      <form method="post" action="{{ route('galika.connections.test',$provider) }}">@csrf
        <button class="btn btn-outline-secondary btn-sm">Run health test</button>
      </form>
    @endif
  </div>
</div>
@endforeach
</div>

<h4>Service credentials</h4>
<p class="text-muted">Only providers that still require an external API credential appear here.</p>
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
