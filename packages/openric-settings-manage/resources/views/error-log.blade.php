@extends('theme::layouts.1col')

@section('title', 'Error Log')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-exclamation-triangle me-2"></i>Error Log</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    {{-- Stats --}}
    <div class="row mb-3">
        <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="h4 mb-0 text-danger">{{ $openCount }}</div><small>Open</small></div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="h4 mb-0 text-success">{{ $resolvedCount }}</div><small>Resolved</small></div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="h4 mb-0 text-warning">{{ $unreadCount }}</div><small>Unread</small></div></div></div>
        <div class="col-md-3"><div class="card text-center"><div class="card-body py-2"><div class="h4 mb-0">{{ $todayCount }}</div><small>Today</small></div></div></div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label">Status</label><select name="status" class="form-select form-select-sm"><option value="">All</option><option value="open" {{ $filters['status'] === 'open' ? 'selected' : '' }}>Open</option><option value="resolved" {{ $filters['status'] === 'resolved' ? 'selected' : '' }}>Resolved</option></select></div>
                <div class="col-md-2"><label class="form-label">Level</label><select name="level" class="form-select form-select-sm"><option value="">All</option>@foreach (['error', 'warning', 'info', 'debug'] as $l)<option value="{{ $l }}" {{ $filters['level'] === $l ? 'selected' : '' }}>{{ ucfirst($l) }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Search</label><input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] }}"></div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('settings.error-log') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    <form method="POST" action="{{ route('settings.error-log') }}" class="d-inline">@csrf<button name="mark_read" value="1" class="btn btn-sm btn-outline-info">Mark All Read</button></form>
                    <form method="POST" action="{{ route('settings.error-log') }}" class="d-inline">@csrf<button name="resolve_all" value="1" class="btn btn-sm btn-outline-success">Resolve All</button></form>
                </div>
            </form>
        </div>
    </div>

    {{-- Error list --}}
    @if ($entries->isEmpty())
        <div class="alert alert-success">No errors found.</div>
    @else
    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Level</th><th>Message</th><th>File</th><th>Date</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    @foreach ($entries as $entry)
                    <tr>
                        <td>
                            @php $badgeClass = match($entry->level ?? 'error') { 'error' => 'bg-danger', 'warning' => 'bg-warning text-dark', 'info' => 'bg-info', default => 'bg-secondary' }; @endphp
                            <span class="badge {{ $badgeClass }}">{{ $entry->level ?? 'error' }}</span>
                        </td>
                        <td>{{ Str::limit($entry->message ?? '', 80) }}</td>
                        <td><small class="text-muted">{{ Str::limit($entry->file ?? '', 40) }}:{{ $entry->line ?? '' }}</small></td>
                        <td><small>{{ $entry->created_at }}</small></td>
                        <td>{{ $entry->resolved_at ? 'Resolved' : 'Open' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('settings.error-log') }}" class="d-inline">
                                @csrf
                                @if ($entry->resolved_at)
                                    <input type="hidden" name="reopen_id" value="{{ $entry->id }}">
                                    <button class="btn btn-sm btn-outline-warning" title="Reopen"><i class="fas fa-undo"></i></button>
                                @else
                                    <input type="hidden" name="resolve_id" value="{{ $entry->id }}">
                                    <button class="btn btn-sm btn-outline-success" title="Resolve"><i class="fas fa-check"></i></button>
                                @endif
                            </form>
                            <form method="POST" action="{{ route('settings.error-log') }}" class="d-inline">
                                @csrf
                                <input type="hidden" name="delete_id" value="{{ $entry->id }}">
                                <button class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if ($totalPages > 1)
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            @for ($p = 1; $p <= $totalPages; $p++)
                <li class="page-item {{ $p === $page ? 'active' : '' }}"><a class="page-link" href="{{ route('settings.error-log', array_merge($filters, ['page' => $p])) }}">{{ $p }}</a></li>
            @endfor
        </ul>
    </nav>
    @endif
    @endif
</div>
@endsection
