@extends('theme::layouts.1col')
@section('title', 'Security Dashboard')
@section('content')
<h1><i class="bi bi-shield-lock"></i> Security Dashboard</h1>
<div class="row mb-4">
    <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h5 class="card-title">Cleared Users</h5><h2>{{ $stats['total_users'] ?? 0 }}</h2><small>With clearance</small></div></div></div>
    <div class="col-md-3"><div class="card bg-warning text-dark"><div class="card-body"><h5 class="card-title">Pending Requests</h5><h2>{{ $stats['active_requests'] ?? 0 }}</h2><small>Awaiting review</small></div></div></div>
    <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><h5 class="card-title">Classified Objects</h5><h2>{{ $stats['classified_objects'] ?? 0 }}</h2><small>Active classifications</small></div></div></div>
    <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h5 class="card-title">Compartments</h5><h2>{{ $stats['compartments'] ?? 0 }}</h2><small>Defined</small></div></div></div>
</div>
<div class="card"><div class="card-header"><h5 class="mb-0"><i class="bi bi-link-45deg"></i> Quick Links</h5></div><div class="card-body"><div class="row">
    <div class="col-md-3"><a href="{{ route('acl.security-index') }}" class="btn btn-outline-primary btn-block mb-2 w-100"><i class="bi bi-people"></i> Manage Clearances</a></div>
    <div class="col-md-3"><a href="{{ route('acl.compartments') }}" class="btn btn-outline-secondary btn-block mb-2 w-100"><i class="bi bi-diagram-3"></i> Compartments</a></div>
    <div class="col-md-3"><a href="{{ route('acl.security-audit-trail') }}" class="btn btn-outline-info btn-block mb-2 w-100"><i class="bi bi-clock-history"></i> Audit Log</a></div>
    <div class="col-md-3"><a href="{{ route('acl.security-report') }}" class="btn btn-outline-success btn-block mb-2 w-100"><i class="bi bi-bar-chart"></i> Reports</a></div>
</div></div></div>
@endsection
