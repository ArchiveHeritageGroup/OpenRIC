@extends('theme::layouts.1col')

@section('title', 'AI Rights Details')

@section('content')
<div class="container py-4">
    <h1><i class="fas fa-key me-2"></i>AI Rights Details</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Entity Information</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Entity IRI</dt>
                <dd class="col-sm-9"><code>{{ $rights->entity_iri }}</code></dd>
                <dt class="col-sm-3">Entity Type</dt>
                <dd class="col-sm-9">{{ $rights->entity_type ?? 'Not specified' }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">AI Permissions</div>
        <div class="card-body">
            <table class="table mb-0">
                <tr class="{{ $rights->ai_allowed ? 'table-success' : 'table-danger' }}">
                    <th>AI Allowed</th>
                    <td>{{ $rights->ai_allowed ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <th>Summarisation</th>
                    <td>{{ $rights->summarisation_allowed ? 'Allowed' : 'Not Allowed' }}</td>
                </tr>
                <tr>
                    <th>Embedding</th>
                    <td>{{ $rights->embedding_allowed ? 'Allowed' : 'Not Allowed' }}</td>
                </tr>
                <tr>
                    <th>Training Reuse</th>
                    <td>{{ $rights->training_reuse_allowed ? 'Allowed' : 'Not Allowed' }}</td>
                </tr>
                <tr>
                    <th>Redaction Required</th>
                    <td>{{ $rights->redaction_required ? 'Yes' : 'No' }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($rights->ai_review_notes)
    <div class="card mb-4">
        <div class="card-header">Review Notes</div>
        <div class="card-body">{{ $rights->ai_review_notes }}</div>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">Validity Period</div>
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Valid From</dt>
                <dd class="col-sm-9">{{ $rights->valid_from ?? 'No start date' }}</dd>
                <dt class="col-sm-3">Valid Until</dt>
                <dd class="col-sm-9">{{ $rights->valid_until ?? 'No end date' }}</dd>
            </dl>
        </div>
    </div>

    <div class="d-flex gap-2">
        <a href="{{ route('ai-governance.rights.index') }}" class="btn btn-secondary">Back to List</a>
        <form method="POST" action="{{ route('ai-governance.rights.destroy', $rights->id) }}" onsubmit="return confirm('Delete this entry?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>
@endsection
