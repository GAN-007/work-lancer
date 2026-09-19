@extends('galika.layout')
@section('title','GALIKA Wealth Engine')
@section('content')
<h2>Wealth Execution</h2>
<div class="card p-4 mb-4"><h5>Add opportunity</h5><form method="post" action="{{ route('galika.wealth.create') }}">@csrf
<div class="row g-2">
<div class="col-md-2"><select class="form-select" name="lane">@foreach(['CONSULTING','B2B','TENDER','PARTNERSHIP','PRODUCT','IP','OTHER'] as $x)<option>{{ $x }}</option>@endforeach</select></div>
<div class="col-md-3"><input class="form-control" name="title" placeholder="Opportunity title" required></div>
<div class="col-md-2"><input class="form-control" name="counterparty" placeholder="Counterparty"></div>
<div class="col-md-3"><input class="form-control" name="source_url" placeholder="https://..."></div>
<div class="col-md-1"><input class="form-control" name="estimated_value" type="number" step="0.01" placeholder="Value"></div>
<div class="col-md-1"><input class="form-control" name="currency" maxlength="3" placeholder="KES"></div>
</div><button class="btn btn-primary mt-3">Queue</button></form></div>
<div class="card p-3"><div class="table-responsive"><table class="table"><thead><tr><th>Lane</th><th>Title</th><th>Counterparty</th><th>State</th><th>Next action</th></tr></thead><tbody>@foreach($items as $i)<tr><td>{{ $i->lane }}</td><td>{{ $i->title }}</td><td>{{ $i->counterparty }}</td><td>{{ $i->state }}</td><td>{{ data_get($i->execution_plan,'next_action','—') }}</td></tr>@endforeach</tbody></table></div>{{ $items->links() }}</div>
@endsection