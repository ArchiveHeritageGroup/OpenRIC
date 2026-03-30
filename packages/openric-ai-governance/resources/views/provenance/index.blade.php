@extends('theme::layouts.1col')

@section('title', 'AI Output Provenance')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-history me-2"></i>AI Output Provenance</h1>
        <a href="{{ route('ai-governance.provenance.pending') }}" class="btn btn-warning">
            <i class="fas fa-clock me-1"></i>Pending Reviews
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Entity</th>
                        <th>Action</th>
                        <th>Model</th>
                        <th>Confidence</th>
                        <th>Approved</th>
                        <th>Date</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($outputs as $output)
                    <tr>
                        <td><small>{{ Str::limit($output->entity_iri, 35) }}</small></td>
                        <td><span class="badge bg-info">{{ $output->action }}</span></td>
                        <td><small>{{ $output->model_version ?? '-' }}</small></td>
                        <td>
                            @if($output->confidence_score)
                                <span class="badge bg-{{ $output->confidence_score > 0.8 ? 'success' : ($output->confidence_score > 0.5 ? 'warning' : 'danger') }}">
                                    {{ number_format($output->confidence_score * 100, 0) }}%
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($output->approved)
                                <span class="badge bg-success">Approved</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </td>
                        <td><small>{{ $output->created_at }}</small></td>
                        <td>
                            <a href="{{ route('ai-governance.provenance.show', $output->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No outputs recorded.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $outputs->links() }}
        </div>
    </div>
</div>
@endsection
