@extends('galika.layout')
@section('title','GALIKA Relationships')
@section('content')
<h2>People & Referral Graph</h2>
<div class="card p-3 mt-3"><div class="table-responsive"><table class="table"><thead><tr><th>Person</th><th>Company</th><th>Role</th><th>Relationship</th><th>Strength</th><th>Last contact</th></tr></thead><tbody>
@foreach($relationships as $r)<tr><td>{{ $r->person?->name }}</td><td>{{ $r->person?->employer?->canonical_name }}</td><td>{{ $r->person?->role }}</td><td>{{ $r->person?->relationship_type }}</td><td>{{ $r->strength }}</td><td>{{ optional($r->last_contact_at)->diffForHumans() }}</td></tr>@endforeach
</tbody></table></div>{{ $relationships->links() }}</div>
@endsection