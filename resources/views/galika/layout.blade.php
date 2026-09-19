<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','GALIKA')</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{background:#f5f7fb}.sidebar{min-height:100vh;background:#111827;color:#fff}.sidebar a{color:#cbd5e1;text-decoration:none;display:block;padding:.65rem 1rem;border-radius:.5rem}.sidebar a:hover,.sidebar a.active{background:#1f2937;color:#fff}.card{border:0;box-shadow:0 4px 18px rgba(15,23,42,.06)}.status{font-size:.75rem;font-weight:700;padding:.25rem .55rem;border-radius:999px;background:#eef2ff}.metric{font-size:1.8rem;font-weight:700}</style>
</head>
<body>
<div class="container-fluid"><div class="row">
<aside class="col-12 col-md-3 col-lg-2 sidebar p-3">
<h4 class="mb-4">GALIKA</h4>
<a href="{{ route('galika.dashboard') }}">Command Center</a>
<a href="{{ route('galika.profile') }}">Career Profile</a>
<a href="{{ route('galika.opportunities') }}">Opportunity Inbox</a>
<a href="{{ route('galika.applications') }}">Application Ledger</a>
<a href="{{ route('galika.decisions') }}">Decision Queue</a>
<a href="{{ route('galika.analytics') }}">Analytics</a>
<hr class="border-secondary"><a href="{{ url('/') }}">Work-Lancer</a>
</aside>
<main class="col-12 col-md-9 col-lg-10 p-3 p-md-4">@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif @yield('content')</main>
</div></div></body></html>