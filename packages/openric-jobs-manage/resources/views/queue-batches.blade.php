@extends('theme::layouts.1col')
@section('title', 'Queue Batches')
@section('content')
<div class="d-flex align-items-center mb-3"><h1 class="h3 mb-0"><i class="fas fa-layer-group me-2"></i>Queue Batches</h1></div>
@if(isset($rows) && count($rows))
    <div class="table-responsive"><table class="table table-bordered table-hover mb-0">
        <thead><tr><th>#</th><th>Batch</th><th>Jobs</th><th>Completed</th><th>Failed</th><th>Status</th></tr></thead>
        <tbody>@foreach($rows as $row)<tr><td>{{ $row->id ?? '' }}</td><td>{{ $row->name ?? '' }}</td><td>{{ $row->total_jobs ?? 0 }}</td><td>{{ $row->pending_jobs ?? 0 }}</td><td>{{ $row->failed_jobs ?? 0 }}</td><td>@if(($row->finished_at ?? null))<span class="badge bg-success">Finished</span>@else<span class="badge bg-primary">Running</span>@endif</td></tr>@endforeach</tbody>
    </table></div>
@else<div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No batch records found.</div>@endif
<div class="mt-3"><a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a></div>
@endsection
