@extends('theme::layouts.1col')

@section('title', 'Add AI Rights Entry')

@section('content')
<div class="container py-4">
    <h1><i class="fas fa-key me-2"></i>Add AI Rights Entry</h1>

    <form method="POST" action="{{ route('ai-governance.rights.store') }}">
        @csrf
        <div class="card mb-4">
            <div class="card-header">Entity Information</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="entity_iri" class="form-label">Entity IRI <span class="text-danger">*</span></label>
                    <input type="text" name="entity_iri" id="entity_iri" class="form-control" required>
                    <div class="form-text">The IRI of the record, agent, or collection this applies to.</div>
                </div>
                <div class="mb-3">
                    <label for="entity_type" class="form-label">Entity Type</label>
                    <select name="entity_type" id="entity_type" class="form-select">
                        <option value="">Select type...</option>
                        <option value="record">Record</option>
                        <option value="agent">Agent</option>
                        <option value="collection">Collection</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">AI Permissions</div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="ai_allowed" id="ai_allowed" class="form-check-input" value="1">
                        <label for="ai_allowed" class="form-check-label">AI Allowed</label>
                    </div>
                    <div class="form-text">Enable basic AI operations on this entity.</div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="summarisation_allowed" id="summarisation_allowed" class="form-check-input" value="1">
                        <label for="summarisation_allowed" class="form-check-label">Summarisation Allowed</label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="embedding_allowed" id="embedding_allowed" class="form-check-input" value="1">
                        <label for="embedding_allowed" class="form-check-label">Embedding/Indexing Allowed</label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="training_reuse_allowed" id="training_reuse_allowed" class="form-check-input" value="1">
                        <label for="training_reuse_allowed" class="form-check-label">Training/Reuse Allowed</label>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="redaction_required" id="redaction_required" class="form-check-input" value="1">
                        <label for="redaction_required" class="form-check-label">Redaction Required Before AI Processing</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Additional Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="ai_review_notes" class="form-label">Review Notes</label>
                    <textarea name="ai_review_notes" id="ai_review_notes" class="form-control" rows="3"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="valid_from" class="form-label">Valid From</label>
                        <input type="date" name="valid_from" id="valid_from" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="valid_until" class="form-label">Valid Until</label>
                        <input type="date" name="valid_until" id="valid_until" class="form-control">
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('ai-governance.rights.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
