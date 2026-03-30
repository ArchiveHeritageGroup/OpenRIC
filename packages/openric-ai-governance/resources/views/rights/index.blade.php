@extends('theme::layouts.1col')

@section('title', 'AI Rights Matrix')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-key me-2"></i>AI Rights & Restrictions</h1>
        <a href="{{ route('ai-governance.rights.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Rights Entry
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Entity IRI</th>
                        <th>AI Allowed</th>
                        <th>Summarisation</th>
                        <th>Embedding</th>
                        <th>Training</th>
                        <th>Redaction</th>
                        <th>Valid Until</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rights as $right)
                    <tr>
                        <td><small>{{ Str::limit($right->entity_iri, 40) }}</small></td>
                        <td>
                            @if($right->ai_allowed)
                                <span class="badge bg-success">Allowed</span>
                            @else
                                <span class="badge bg-danger">Restricted</span>
                            @endif
                        </td>
                        <td>@if($right->summarisation_allowed)<i class="fas fa-check text-success"></i>@else<i class="fas fa-times text-danger"></i>@endif</td>
                        <td>@if($right->embedding_allowed)<i class="fas fa-check text-success"></i>@else<i class="fas fa-times text-danger"></i>@endif</td>
                        <td>@if($right->training_reuse_allowed)<i class="fas fa-check text-success"></i>@else<i class="fas fa-times text-danger"></i>@endif</td>
                        <td>@if($right->redaction_required)<i class="fas fa-check text-warning"></i>@else<i class="fas fa-minus text-muted"></i>@endif</td>
                        <td><small>{{ $right->valid_until ?? 'No limit' }}</small></td>
                        <td>
                            <a href="{{ route('ai-governance.rights.show', $right->id) }}" class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No AI rights entries found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $rights->links() }}
        </div>
    </div>
</div>
@endsection
