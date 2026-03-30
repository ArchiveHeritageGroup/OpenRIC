@extends('theme::layouts.1col')

@section('title', 'Create AI Readiness Profile')

@section('content')
<div class="container py-4">
    <h1><i class="fas fa-clipboard-check me-2"></i>Create AI Readiness Profile</h1>

    <form method="POST" action="{{ route('ai-governance.readiness.store') }}">
        @csrf
        <div class="card mb-4">
            <div class="card-header">Collection Information</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="collection_iri" class="form-label">Collection IRI <span class="text-danger">*</span></label>
                    <input type="text" name="collection_iri" id="collection_iri" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="collection_title" class="form-label">Collection Title</label>
                    <input type="text" name="collection_title" id="collection_title" class="form-control">
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">AI Readiness Assessment</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="digitization_completeness" class="form-label">Digitization Completeness</label>
                    <select name="digitization_completeness" id="digitization_completeness" class="form-select">
                        <option value="not_started">Not Started</option>
                        <option value="partial">Partial</option>
                        <option value="complete">Complete</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="corpus_completeness" class="form-label">Corpus Completeness</label>
                    <select name="corpus_completeness" id="corpus_completeness" class="form-select">
                        <option value="partial" selected>Partial</option>
                        <option value="complete">Complete</option>
                        <option value="sampled">Sampled</option>
                        <option value="biased">Biased</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Documentation</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="known_gaps" class="form-label">Known Gaps</label>
                    <textarea name="known_gaps" id="known_gaps" class="form-control" rows="3"></textarea>
                </div>
                <div class="mb-3">
                    <label for="excluded_records" class="form-label">Excluded Records</label>
                    <textarea name="excluded_records" id="excluded_records" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label for="legal_exclusions" class="form-label">Legal Exclusions</label>
                    <textarea name="legal_exclusions" id="legal_exclusions" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label for="privacy_exclusions" class="form-label">Privacy Exclusions</label>
                    <textarea name="privacy_exclusions" id="privacy_exclusions" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label for="representational_bias_notes" class="form-label">Representational Bias Notes</label>
                    <textarea name="representational_bias_notes" id="representational_bias_notes" class="form-control" rows="2"></textarea>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save Profile</button>
            <a href="{{ route('ai-governance.readiness.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
