@extends('theme::layouts.1col')

@section('title', 'AI Readiness Profiles')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="fas fa-clipboard-check me-2"></i>AI Readiness Profiles</h1>
        <a href="{{ route('ai-governance.readiness.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i>Add Profile
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Collection</th>
                        <th>Digitization</th>
                        <th>Completeness</th>
                        <th>Last Reviewed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($profiles as $profile)
                    <tr>
                        <td><small>{{ Str::limit($profile->collection_iri, 40) }}</small></td>
                        <td><span class="badge bg-secondary">{{ str_replace('_', ' ', $profile->digitization_completeness) }}</span></td>
                        <td><span class="badge bg-{{ $profile->corpus_completeness === 'complete' ? 'success' : ($profile->corpus_completeness === 'biased' ? 'danger' : 'warning') }}">{{ str_replace('_', ' ', $profile->corpus_completeness) }}</span></td>
                        <td><small>{{ $profile->last_reviewed_at ?? '-' }}</small></td>
                        <td>
                            <a href="{{ route('ai-governance.readiness.edit', $profile->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No readiness profiles.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            {{ $profiles->links() }}
        </div>
    </div>
</div>
@endsection
