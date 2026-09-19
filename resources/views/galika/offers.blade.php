@extends('galika.layout')
@section('title','GALIKA Offer OS')
@section('content')
<h2>Offer OS</h2>
<div class="card p-3 mt-3"><div class="table-responsive"><table class="table"><thead><tr><th>Company</th><th>Role</th><th>State</th><th>Base</th><th>Currency</th><th>Deadline</th></tr></thead><tbody>
@foreach($offers as $o)<tr><td>{{ $o->application?->opportunity?->employer }}</td><td>{{ $o->application?->opportunity?->title }}</td><td>{{ $o->state }}</td><td>{{ $o->base_comp }}</td><td>{{ $o->currency }}</td><td>{{ optional($o->deadline_at)->toDayDateTimeString() }}</td></tr>@endforeach
</tbody></table></div>{{ $offers->links() }}</div>
@endsection