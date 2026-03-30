@extends('theme::layouts.1col')
@section('title', 'Manage Jobs')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Manage Jobs</h1>
</div>

@include('theme::partials.alerts')

<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card text-center h-100"><div class="card-body py-3"><div class="fs-3 fw-bold">{{ $stats['total'] ?? 0 }}</div><div class="text-muted small">Total</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center h-100 border-primary"><div class="card-body py-3"><div class="fs-3 fw-bold text-primary">{{ $stats['pending'] ?? 0 }}</div><div class="text-muted small">Pending</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center h-100 border-success"><div class="card-body py-3"><div class="fs-3 fw-bold text-success">{{ $stats['completed'] ?? 0 }}</div><div class="text-muted small">Completed</div></div></div></div>
    <div class="col-6 col-md-3"><div class="card text-center h-100 border-danger"><div class="card-body py-3"><div class="fs-3 fw-bold text-danger">{{ $stats['failed'] ?? 0 }}</div><div class="text-muted small">Failed</div></div></div></div>
</div>

<div class="d-flex flex-wrap gap-2 mb-3">
    <ul class="nav nav-pills">
        <li class="nav-item"><a href="{{ route('jobs.index') }}" class="nav-link {{ ($currentFilter ?? '') === '' ? 'active' : '' }}">All</a></li>
        <li class="nav-item"><a href="{{ route('jobs.index', ['filter' => 'pending']) }}" class="nav-link {{ ($currentFilter ?? '') === 'pending' ? 'active' : '' }}">Pending</a></li>
        <li class="nav-item"><a href="{{ route('jobs.failed') }}" class="nav-link {{ ($currentFilter ?? '') === 'failed' ? 'active' : '' }}">Failed</a></li>
    </ul>
    <div class="d-flex flex-wrap gap-2 ms-auto">
        <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-sync-alt"></i> Refresh</a>
        @if(($stats['failed'] ?? 0) > 0)
            <form action="{{ route('jobs.clearFailed') }}" method="POST" class="d-inline" onsubmit="return confirm('Clear all failed jobs?')">@csrf
                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fas fa-trash-alt"></i> Clear Failed</button>
            </form>
        @endif
    </div>
</div>

@if(isset($results) && count($results) > 0)
    <div class="table-responsive mb-3">
        <table class="table table-bordered table-hover mb-0">
            <thead><tr><th>Job</th><th>Queue</th><th>Attempts</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($results as $job)
                    <tr>
                        <td>{{ $job->display_name ?? 'Unknown' }}</td>
                        <td><span class="badge bg-secondary">{{ $job->queue ?? 'default' }}</span></td>
                        <td>{{ $job->attempts ?? 0 }}</td>
                        <td>{{ $job->queued_at ?? '' }}</td>
                        <td>
                            @if(isset($job->uuid))
                                <form action="{{ route('jobs.delete') }}" method="POST" class="d-inline">@csrf
                                    <input type="hidden" name="uuid" value="{{ $job->uuid }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-info">No jobs found.</div>
@endif

<div class="text-muted small text-center mb-3">
    Showing {{ count($results ?? []) }} of {{ $total ?? 0 }} job(s) | Page {{ $page ?? 1 }} of {{ $lastPage ?? 1 }}
</div>
@endsection
