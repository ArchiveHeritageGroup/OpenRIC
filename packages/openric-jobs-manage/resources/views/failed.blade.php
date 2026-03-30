@extends('theme::layouts.1col')
@section('title', 'Failed Jobs')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Failed Jobs</h1>
    <a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>All Jobs</a>
</div>

@include('theme::partials.alerts')

@if(isset($results) && count($results) > 0)
    <div class="table-responsive mb-3">
        <table class="table table-bordered table-hover mb-0">
            <thead><tr><th>Job</th><th>Queue</th><th>Failed At</th><th>Exception</th><th>Actions</th></tr></thead>
            <tbody>
                @foreach($results as $job)
                    <tr>
                        <td>{{ $job->display_name ?? 'Unknown' }}</td>
                        <td><span class="badge bg-secondary">{{ $job->queue ?? 'default' }}</span></td>
                        <td>{{ $job->failed_at ?? '' }}</td>
                        <td><small class="text-danger">{{ $job->short_exception ?? '' }}</small></td>
                        <td>
                            <form action="{{ route('jobs.retry') }}" method="POST" class="d-inline">@csrf
                                <input type="hidden" name="uuid" value="{{ $job->uuid }}">
                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Retry"><i class="fas fa-redo"></i></button>
                            </form>
                            <form action="{{ route('jobs.delete') }}" method="POST" class="d-inline">@csrf
                                <input type="hidden" name="uuid" value="{{ $job->uuid }}">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="alert alert-success"><i class="fas fa-check-circle me-1"></i>No failed jobs.</div>
@endif
@endsection
