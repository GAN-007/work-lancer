@extends('galika.layout')
@section('title','GALIKA Analytics')
@section('content')
<h2>Career Funnel Analytics</h2>
<div class="row g-3 mt-2">
@foreach($outcomes as $label=>$value)
<div class="col-6 col-md-4 col-xl"><div class="card p-3"><div class="text-muted text-capitalize">{{ str_replace('_',' ',$label) }}</div><div class="metric">{{ $value }}</div></div></div>
@endforeach
<div class="col-12"><div class="card p-3"><div class="text-muted">Average confirmed-submit latency</div><div class="metric">{{ $avg ? round($avg).'s' : '—' }}</div></div></div>
</div>
@endsection