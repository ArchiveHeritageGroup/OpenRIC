@extends('theme::layouts.1col')
@section('title', 'Add Language Settings')
@section('content')
<div class="container py-4">
    <h1><i class="fas fa-language me-2"></i>Add Language AI Settings</h1>
    <form method="POST" action="{{ route('ai-governance.languages.store') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-header">Language</div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3"><label for="language_code" class="form-label">Code <span class="text-danger">*</span></label><input type="text" name="language_code" id="language_code" class="form-control" maxlength="10" required placeholder="e.g. af, en, zu"></div>
                    <div class="col-md-8 mb-3"><label for="language_name" class="form-label">Name <span class="text-danger">*</span></label><input type="text" name="language_name" id="language_name" class="form-control" required placeholder="e.g. Afrikaans"></div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">AI Permissions</div>
            <div class="card-body">
                <div class="form-check mb-2"><input type="checkbox" name="ai_allowed" id="ai_allowed" class="form-check-input" value="1" checked><label for="ai_allowed" class="form-check-label">AI Allowed</label></div>
                <div class="form-check mb-2"><input type="checkbox" name="translation_allowed" id="translation_allowed" class="form-check-input" value="1" checked><label for="translation_allowed" class="form-check-label">Translation Allowed</label></div>
                <div class="form-check mb-2"><input type="checkbox" name="embedding_enabled" id="embedding_enabled" class="form-check-input" value="1" checked><label for="embedding_enabled" class="form-check-label">Embedding Enabled</label></div>
                <div class="form-check mb-2"><input type="checkbox" name="competency_required" id="competency_required" class="form-check-input" value="1"><label for="competency_required" class="form-check-label">Human Competency Review Required</label></div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">Warning & Review</div>
            <div class="card-body">
                <div class="mb-3"><label for="access_warning" class="form-label">Access Warning</label><textarea name="access_warning" id="access_warning" class="form-control" rows="2" placeholder="Warning message to display..."></textarea></div>
                <div class="mb-3"><label for="competency_languages" class="form-label">Required Reviewer Languages</label><input type="text" name="competency_languages" id="competency_languages" class="form-control" placeholder="e.g. en, af"></div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('ai-governance.languages.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
