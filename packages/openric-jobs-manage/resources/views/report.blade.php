@extends('theme::layouts.1col')
@section('title', 'Jobs Report')
@section('content')
<div class="d-flex align-items-center mb-3"><h1 class="h3 mb-0"><i class="fas fa-chart-bar me-2"></i>Jobs Report</h1></div>
<div class="row">
    <div class="col-md-3 mb-3"><div class="card text-center h-100"><div class="card-body"><i class="fas fa-tasks fa-2x text-primary mb-2"></i><h5>{{ $totalJobs ?? 0 }}</h5><p class="text-muted small mb-0">Total Jobs</p></div></div></div>
    <div class="col-md-3 mb-3"><div class="card text-center h-100"><div class="card-body"><i class="fas fa-check-circle fa-2x text-success mb-2"></i><h5>{{ $completedJobs ?? 0 }}</h5><p class="text-muted small mb-0">Completed</p></div></div></div>
    <div class="col-md-3 mb-3"><div class="card text-center h-100"><div class="card-body"><i class="fas fa-times-circle fa-2x text-danger mb-2"></i><h5>{{ $failedJobs ?? 0 }}</h5><p class="text-muted small mb-0">Failed</p></div></div></div>
    <div class="col-md-3 mb-3"><div class="card text-center h-100"><div class="card-body"><i class="fas fa-clock fa-2x text-info mb-2"></i><h5>{{ $avgDuration ?? '0s' }}</h5><p class="text-muted small mb-0">Avg Duration</p></div></div></div>
</div>
<div class="mt-3"><a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a></div>
@endsection
