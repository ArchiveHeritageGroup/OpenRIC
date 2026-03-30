@extends('theme::layouts.1col')

@section('title', 'AI Governance Dashboard')

@section('content')
<div class="container-fluid py-4">
    <h1 class="mb-4">
        <i class="fas fa-robot me-2"></i>AI Governance Dashboard
    </h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h5 class="card-title">Pending Outputs</h5>
                    <h2>{{ $stats['pending_outputs'] }}</h2>
                    <a href="{{ route('ai-governance.provenance.pending') }}" class="btn btn-dark btn-sm">Review</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card {{ $stats['critical_bias'] > 0 ? 'bg-danger text-white' : 'bg-secondary text-white' }}">
                <div class="card-body">
                    <h5 class="card-title">Unresolved Bias</h5>
                    <h2>{{ $stats['unresolved_bias'] }}</h2>
                    @if($stats['critical_bias'] > 0)
                        <span class="badge bg-dark">{{ $stats['critical_bias'] }} critical</span>
                    @endif
                    <a href="{{ route('ai-governance.bias.index', ['resolved' => 0]) }}" class="btn btn-light btn-sm mt-2">Review</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending Projects</h5>
                    <h2>{{ $stats['pending_projects'] }}</h2>
                    <a href="{{ route('ai-governance.projects.index') }}" class="btn btn-light btn-sm">Review</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">AI Rights Status</h5>
                    <h2>{{ $stats['ai_allowed_entities'] }}</h2>
                    <small>allowed / {{ $stats['ai_restricted_entities'] }} restricted</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Links -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="btn-group" role="group">
                <a href="{{ route('ai-governance.rights.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-key me-1"></i>AI Rights
                </a>
                <a href="{{ route('ai-governance.provenance.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-history me-1"></i>Provenance
                </a>
                <a href="{{ route('ai-governance.readiness.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-clipboard-check me-1"></i>Readiness
                </a>
                <a href="{{ route('ai-governance.projects.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-rocket me-1"></i>Projects
                </a>
                <a href="{{ route('ai-governance.metrics.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-chart-line me-1"></i>Metrics
                </a>
                <a href="{{ route('ai-governance.derivatives.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-file-export me-1"></i>Derivatives
                </a>
                <a href="{{ route('ai-governance.languages.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-language me-1"></i>Languages
                </a>
            </div>
        </div>
    </div>

    <!-- Pending Outputs -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending AI Outputs</h5>
                </div>
                <div class="card-body">
                    @if($pendingOutputs->isEmpty())
                        <p class="text-muted mb-0">No pending outputs.</p>
                    @else
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Entity</th>
                                    <th>Action</th>
                                    <th>Confidence</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($pendingOutputs as $output)
                                <tr>
                                    <td><small>{{ Str::limit($output->entity_iri, 30) }}</small></td>
                                    <td><span class="badge bg-secondary">{{ $output->action }}</span></td>
                                    <td>
                                        @if($output->confidence_score)
                                            <span class="badge bg-{{ $output->confidence_score > 0.8 ? 'success' : ($output->confidence_score > 0.5 ? 'warning' : 'danger') }}">
                                                {{ number_format($output->confidence_score * 100, 0) }}%
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('ai-governance.provenance.show', $output->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

        <!-- Unresolved Bias -->
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Unresolved Bias/Harm Issues</h5>
                </div>
                <div class="card-body">
                    @if($unresolvedBias->isEmpty())
                        <p class="text-success mb-0"><i class="fas fa-check-circle me-1"></i>No unresolved issues.</p>
                    @else
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Severity</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($unresolvedBias as $record)
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $record->severity === 'critical' ? 'danger' : ($record->severity === 'high' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($record->severity) }}
                                        </span>
                                    </td>
                                    <td><small>{{ str_replace('_', ' ', ucfirst($record->category)) }}</small></td>
                                    <td><small>{{ Str::limit($record->description, 40) }}</small></td>
                                    <td>
                                        <a href="{{ route('ai-governance.bias.show', $record->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
