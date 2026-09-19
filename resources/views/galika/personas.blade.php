@extends('galika.layout')
@section('title','GALIKA Career Personas')
@section('content')
<h2>Career Personas</h2>
<p class="text-muted">Different truthful professional presentations backed by the same verified evidence graph.</p>
<div class="row g-3">@foreach($personas as $p)<div class="col-md-6 col-xl-4"><div class="card p-3 h-100"><h5>{{ $p->name }}</h5><div>{{ $p->headline }}</div><small class="text-muted">{{ implode(', ',$p->target_roles ?? []) }}</small><div class="mt-2">Performance: {{ $p->performance_score ?? '—' }}</div><span class="badge {{ $p->active ? 'bg-success':'bg-secondary' }} mt-2">{{ $p->active ? 'ACTIVE':'PAUSED' }}</span></div></div>@endforeach</div><div class="mt-3">{{ $personas->links() }}</div>
@endsection