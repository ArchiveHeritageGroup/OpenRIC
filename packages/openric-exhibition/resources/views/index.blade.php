@extends('theme::layouts.1col')

@section('title', 'Exhibitions')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-palette me-2"></i>Exhibitions</h1>
        @auth
        <a href="{{ route('exhibition.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> New Exhibition
        </a>
        @endauth
    </div>

    {{-- Statistics cards --}}
    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="h4 mb-0">{{ $stats['total'] }}</div>
                    <small class="text-muted">Total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="h4 mb-0 text-success">{{ $stats['active'] }}</div>
                    <small class="text-muted">Active</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="h4 mb-0 text-warning">{{ $stats['planning'] }}</div>
                    <small class="text-muted">Planning</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="h4 mb-0 text-secondary">{{ $stats['completed'] }}</div>
                    <small class="text-muted">Completed</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('exhibition.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['status'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All types</option>
                        @foreach ($types as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['exhibition_type'] ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm" value="{{ $filters['search'] ?? '' }}" placeholder="Search exhibitions...">
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search me-1"></i> Filter</button>
                    <a href="{{ route('exhibition.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Results --}}
    @if ($exhibitions->isEmpty())
        <div class="alert alert-info">No exhibitions found.</div>
    @else
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Venue</th>
                        <th>Dates</th>
                        <th>Curator</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($exhibitions as $exhibition)
                    <tr>
                        <td>
                            <a href="{{ route('exhibition.show', $exhibition->id) }}">{{ $exhibition->title }}</a>
                            @if (!empty($exhibition->subtitle))
                                <br><small class="text-muted">{{ $exhibition->subtitle }}</small>
                            @endif
                        </td>
                        <td>{{ $types[$exhibition->exhibition_type] ?? $exhibition->exhibition_type }}</td>
                        <td>
                            @php
                                $badgeClass = match($exhibition->status) {
                                    'active' => 'bg-success',
                                    'planning' => 'bg-warning text-dark',
                                    'preparation' => 'bg-info',
                                    'completed' => 'bg-secondary',
                                    'archived' => 'bg-dark',
                                    default => 'bg-light text-dark',
                                };
                            @endphp
                            <span class="badge {{ $badgeClass }}">{{ $statuses[$exhibition->status] ?? $exhibition->status }}</span>
                        </td>
                        <td>{{ $exhibition->venue ?? '' }}</td>
                        <td>
                            @if ($exhibition->start_date)
                                {{ \Carbon\Carbon::parse($exhibition->start_date)->format('d M Y') }}
                                @if ($exhibition->end_date)
                                    &ndash; {{ \Carbon\Carbon::parse($exhibition->end_date)->format('d M Y') }}
                                @endif
                            @endif
                        </td>
                        <td>{{ $exhibition->curator ?? '' }}</td>
                        <td class="text-end">
                            @auth
                            <a href="{{ route('exhibition.edit', $exhibition->id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                            @endauth
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($pages > 1)
        <nav>
            <ul class="pagination justify-content-center">
                @for ($p = 1; $p <= $pages; $p++)
                    <li class="page-item {{ $p === $page ? 'active' : '' }}">
                        <a class="page-link" href="{{ route('exhibition.index', array_merge($filters, ['page' => $p])) }}">{{ $p }}</a>
                    </li>
                @endfor
            </ul>
        </nav>
        @endif
    @endif
</div>
@endsection
