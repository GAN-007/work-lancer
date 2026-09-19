@extends('galika.layout')
@section('title','GALIKA Campaigns')
@section('content')
<h2>Pursuit Campaigns</h2>
<div class="card p-3 mt-3"><div class="table-responsive"><table class="table"><thead><tr><th>Strategy</th><th>Employer</th><th>Opportunity</th><th>State</th><th>Expected value</th><th>Next action</th></tr></thead><tbody>
@foreach($campaigns as $c)<tr><td>{{ $c->strategy }}</td><td>{{ optional(AppModelsGalikaEmployer::find($c->employer_id))->canonical_name }}</td><td>{{ optional(AppModelsGalikaOpportunity::find($c->opportunity_id))->title }}</td><td>{{ $c->state }}</td><td>{{ $c->expected_value }}</td><td>{{ optional($c->next_action_at)->diffForHumans() }}</td></tr>@endforeach
</tbody></table></div>{{ $campaigns->links() }}</div>
@endsection