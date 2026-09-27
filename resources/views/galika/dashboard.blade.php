@extends('galika.layout')
@section('title','GALIKA Command Center')
@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
  <div>
    <h2>Career Command Center</h2>
    <p class="text-muted mb-0">External outcomes first: applications, buyer conversion, cash, MRR, and runtime truth.</p>
  </div>
  <span class="status">{{ $profile->pause_all_execution ? 'PAUSED' : ($profile->autonomous_apply_enabled ? 'AUTONOMOUS' : 'REVIEW MODE') }}</span>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Revenue invoiced</div><div class="metric">KES {{ number_format((float)($scorecard?->revenue_invoiced ?? 0),2) }}</div></div></div>
  <div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Cash collected</div><div class="metric">KES {{ number_format((float)($scorecard?->cash_collected ?? 0),2) }}</div></div></div>
  <div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">MRR</div><div class="metric">KES {{ number_format((float)($scorecard?->mrr ?? 0),2) }}</div></div></div>
  <div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Customers</div><div class="metric">{{ $customers }}</div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Applications</div><div class="metric">{{ $applications->count() }}</div></div></div>
  <div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Open invoices</div><div class="metric">{{ $openInvoices->count() }}</div></div></div>
  <div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Confirmed payments</div><div class="metric">{{ $recentPayments->count() }}</div></div></div>
  <div class="col-6 col-xl-3"><div class="card p-3"><div class="text-muted">Open incidents</div><div class="metric">{{ $runtimeIncidents->count() }}</div></div></div>
</div>

@if($runtimeIncidents->count())
<div class="card p-3 mb-4 border-danger">
  <h5>Runtime incidents</h5>
  <div class="table-responsive">
    <table class="table align-middle">
      <thead><tr><th>Severity</th><th>Component</th><th>Code</th><th>Last seen</th><th>Message</th></tr></thead>
      <tbody>
      @foreach($runtimeIncidents as $incident)
        <tr>
          <td>{{ $incident->severity }}</td>
          <td>{{ $incident->component }}</td>
          <td>{{ $incident->code }}</td>
          <td>{{ $incident->last_seen_at }}</td>
          <td>{{ $incident->message }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="row g-3 mb-4">
  <div class="col-xl-6">
    <div class="card p-3">
      <h5>Open invoices</h5>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Invoice</th><th>Amount</th><th>Paid</th><th>State</th></tr></thead>
          <tbody>
          @forelse($openInvoices as $invoice)
            <tr>
              <td>{{ $invoice->invoice_number }}</td>
              <td>{{ $invoice->currency }} {{ number_format((float)$invoice->total,2) }}</td>
              <td>{{ $invoice->currency }} {{ number_format((float)$invoice->amount_paid,2) }}</td>
              <td>{{ $invoice->state }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-muted">No open invoices.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-6">
    <div class="card p-3">
      <h5>Confirmed payments</h5>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Provider</th><th>Reference</th><th>Amount</th><th>Confirmed</th></tr></thead>
          <tbody>
          @forelse($recentPayments as $payment)
            <tr>
              <td>{{ $payment->provider }}</td>
              <td>{{ $payment->provider_reference }}</td>
              <td>{{ $payment->currency }} {{ number_format((float)$payment->amount,2) }}</td>
              <td>{{ optional($payment->confirmed_at)->toDateTimeString() }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-muted">No confirmed payments.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-7">
    <div class="card p-3">
      <h5>Latest opportunities</h5>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead><tr><th>Employer</th><th>Role</th><th>Score</th><th>State</th></tr></thead>
          <tbody>
          @foreach($opportunities as $o)
            <tr><td>{{ $o->employer }}</td><td>{{ $o->title }}</td><td>{{ $o->match_score }}</td><td>{{ $o->eligibility }}</td></tr>
          @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-xl-5">
    <div class="card p-3">
      <h5>Recent applications</h5>
      @forelse($applications as $a)
        <div class="border-bottom py-2">
          <strong>{{ $a->opportunity?->employer }}</strong>
          <div>{{ $a->opportunity?->title }}</div>
          <small class="text-muted">{{ $a->status }} · {{ $a->failure_class }}</small>
        </div>
      @empty
        <p class="text-muted">No applications yet.</p>
      @endforelse
    </div>
  </div>
</div>
@endsection
