@extends('galika.layout')
@section('title','GALIKA Command Center')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4"><div><h2>Career Command Center</h2><p class="text-muted mb-0">Autonomous discovery, qualification, application, delivery and response tracking.</p></div><span class="status">{{ $profile->pause_all_execution ? 'PAUSED' : ($profile->autonomous_apply_enabled ? 'AUTONOMOUS' : 'REVIEW MODE') }}</span></div>
<div class="row g-3 mb-4">
<div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Opportunities</div><div class="metric">{{ $opportunities->count() }}</div></div></div>
<div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Applications</div><div class="metric">{{ $applications->count() }}</div></div></div>
<div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Decisions</div><div class="metric">{{ $decisions->count() }}</div></div></div>
<div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Integrations</div><div class="metric">{{ $integrations->count() }}</div></div></div>
</div>
<div class="row g-3"><div class="col-xl-7"><div class="card p-3"><h5>Latest opportunities</h5><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Employer</th><th>Role</th><th>Score</th><th>State</th></tr></thead><tbody>@foreach($opportunities as $o)<tr><td>{{ $o->employer }}</td><td>{{ $o->title }}</td><td>{{ $o->match_score }}</td><td>{{ $o->eligibility }}</td></tr>@endforeach</tbody></table></div></div></div>
<div class="col-xl-5"><div class="card p-3"><h5>Recent applications</h5>@forelse($applications as $a)<div class="border-bottom py-2"><strong>{{ $a->opportunity?->employer }}</strong><div>{{ $a->opportunity?->title }}</div><small class="text-muted">{{ $a->status }} · {{ $a->failure_class }}</small></div>@empty<p class="text-muted">No applications yet.</p>@endforelse</div></div></div>
@endsection