@extends('galika.layout')
@section('title','GALIKA Interview OS')
@section('content')
<h2>Interview OS</h2>
<div class="card p-3 mt-3"><div class="table-responsive"><table class="table"><thead><tr><th>Company</th><th>Role</th><th>Stage</th><th>State</th><th>When</th><th>Prep</th></tr></thead><tbody>
@foreach($interviews as $i)<tr><td>{{ $i->application?->opportunity?->employer }}</td><td>{{ $i->application?->opportunity?->title }}</td><td>{{ $i->stage }}</td><td>{{ $i->state }}</td><td>{{ optional($i->starts_at)->timezone($i->timezone ?: config('app.timezone'))->toDayDateTimeString() }}</td><td>{{ data_get($i->prep_plan,'status','PENDING') }}</td></tr>@endforeach
</tbody></table></div>{{ $interviews->links() }}</div>
@endsection