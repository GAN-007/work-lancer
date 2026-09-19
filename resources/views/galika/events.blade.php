@extends('galika.layout')
@section('title','GALIKA Events')
@section('content')
<h2>Offline & Networking Events</h2>
<div class="card p-3 mt-3"><div class="table-responsive"><table class="table"><thead><tr><th>Event</th><th>Kind</th><th>Location</th><th>When</th><th>Companies</th></tr></thead><tbody>
@foreach($events as $e)<tr><td>@if($e->url)<a href="{{ $e->url }}" target="_blank">{{ $e->title }}</a>@else{{ $e->title }}@endif</td><td>{{ $e->kind }}</td><td>{{ $e->location }}</td><td>{{ optional($e->starts_at)->toDayDateTimeString() }}</td><td>{{ implode(', ',$e->companies ?? []) }}</td></tr>@endforeach
</tbody></table></div>{{ $events->links() }}</div>
@endsection