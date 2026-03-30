@extends('theme::layouts.1col')
@section('title', 'Queue Browser')
@section('content')
<div class="d-flex align-items-center mb-3"><h1 class="h3 mb-0"><i class="fas fa-list me-2"></i>Queue Browser</h1></div>
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5>{{ $stats['pending'] ?? 0 }}</h5><p class="text-muted small mb-0">Pending</p></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5>{{ $stats['completed'] ?? 0 }}</h5><p class="text-muted small mb-0">Completed</p></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5>{{ $stats['failed'] ?? 0 }}</h5><p class="text-muted small mb-0">Failed</p></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><h5>{{ $stats['total'] ?? 0 }}</h5><p class="text-muted small mb-0">Total</p></div></div></div>
</div>
<div class="d-flex gap-2">
    <a href="{{ route('jobs.queue-batches') }}" class="btn btn-outline-secondary"><i class="fas fa-layer-group me-1"></i>Batches</a>
    <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
</div>
@endsection
