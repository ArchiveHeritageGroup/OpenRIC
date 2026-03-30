@extends('theme::layouts.1col')
@section('title', 'AI Derivative Profiles')
@section('content')
<div class="container-fluid py-4">
    <h1><i class="fas fa-file-export me-2"></i>AI Derivative Profiles</h1>
    <a href="{{ route('ai-governance.derivatives.create') }}" class="btn btn-primary mb-3">Add Profile</a>
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead><tr><th>Collection</th><th>OCR</th><th>Metadata</th><th>Chunks</th><th>Redacted</th><th></th></tr></thead>
                <tbody>
                    @forelse($profiles as $p)
                    <tr>
                        <td><small>{{ Str::limit($p->collection_iri, 40) }}</small></td>
                        <td>{!! $p->cleaned_ocr_text ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                        <td>{!! $p->normalised_metadata_export ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                        <td>{!! $p->chunked_retrieval_units ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                        <td>{!! $p->redacted_access_copies ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>' !!}</td>
                        <td><a href="{{ route('ai-governance.derivatives.edit', $p->id) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted">No profiles.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
