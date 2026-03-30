@extends('theme::layouts.1col')

@section('title', 'Bias/Harm Register')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-exclamation-triangle me-2"></i>Bias/Harm Register</h1>
        <a href="{{ route('ai-governance.bias.create') }}" class="btn btn-danger">
            <i class="fas fa-plus me-1"></i>Report Issue
        </a>
    </div>

    <div class="mb-3">
        <div class="btn-group" role="group">
            <a href="{{ route('ai-governance.bias.index', ['resolved' => 0]) }}" class="btn {{ $resolved === '0' ? 'btn-warning' : 'btn-outline-warning' }}">
                Unresolved
            </a>
            <a href="{{ route('ai-governance.bias.index', ['resolved' => 1]) }}" class="btn {{ $resolved === '1' ? 'btn-success' : 'btn-outline-success' }}">
                Resolved
            </a>
            <a href="{{ route('ai-governance.bias.index') }}" class="btn {{ !$resolved ? 'btn-primary' : 'btn-outline-primary' }}">
                All
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Severity</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Entity</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $record)
                    <tr>
                        <td>
                            <span class="badge bg-{{ $record->severity === 'critical' ? 'danger' : ($record->severity === 'high' ? 'warning text-dark' : ($record->severity === 'medium' ? 'info' : 'secondary')) }}">
                                {{ ucfirst($record->severity) }}
                            </span>
                        </td>
                        <td><small>{{ str_replace('_', ' ', ucfirst($record->category)) }}</small></td>
                        <td><small>{{ Str::limit($record->description, 60) }}</small></td>
                        <td><small>{{ $record->entity_iri ? Str::limit($record->entity_iri, 25) : '-' }}</small></td>
                        <td>
                            @if($record->resolved)
                                <span class="badge bg-success">Resolved</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('ai-governance.bias.show', $record->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $records->links() }}
        </div>
    </div>
</div>
@endsection
