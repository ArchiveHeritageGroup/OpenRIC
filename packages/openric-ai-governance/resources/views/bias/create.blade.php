@extends('theme::layouts.1col')

@section('title', 'Report Bias/Harm Issue')

@section('content')
<div class="container py-4">
    <h1><i class="fas fa-exclamation-circle me-2"></i>Report Bias/Harm Issue</h1>

    <form method="POST" action="{{ route('ai-governance.bias.store') }}">
        @csrf
        <div class="card mb-4">
            <div class="card-header">Issue Details</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="entity_iri" class="form-label">Related Entity (Optional)</label>
                    <input type="text" name="entity_iri" id="entity_iri" class="form-control">
                    <div class="form-text">The IRI of the affected record, agent, or collection.</div>
                </div>
                <div class="mb-3">
                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                    <select name="category" id="category" class="form-select" required>
                        <option value="">Select category...</option>
                        <option value="harmful_language">Harmful Language</option>
                        <option value="culturally_sensitive">Culturally Sensitive Content</option>
                        <option value="absent_communities">Absent Communities</option>
                        <option value="contested_description">Contested Description</option>
                        <option value="power_imbalance">Power Imbalance</option>
                        <option value="under_representation">Under-representation</option>
                        <option value="metadata_gap">Metadata Gap</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                    <textarea name="description" id="description" class="form-control" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="severity" class="form-label">Severity</label>
                    <select name="severity" id="severity" class="form-select">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">AI Warning & Mitigation</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="ai_warning" class="form-label">AI Warning</label>
                    <textarea name="ai_warning" id="ai_warning" class="form-control" rows="2" placeholder="Warning text to display for AI processing..."></textarea>
                </div>
                <div class="mb-3">
                    <label for="mitigation_strategy" class="form-label">Mitigation Strategy</label>
                    <textarea name="mitigation_strategy" id="mitigation_strategy" class="form-control" rows="2" placeholder="How should this be addressed..."></textarea>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-danger">Submit Report</button>
            <a href="{{ route('ai-governance.bias.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
