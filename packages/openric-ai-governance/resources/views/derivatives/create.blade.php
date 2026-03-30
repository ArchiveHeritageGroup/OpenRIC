@extends('theme::layouts.1col')
@section('title', 'Create Derivative Profile')
@section('content')
<div class="container py-4">
    <h1><i class="fas fa-file-export me-2"></i>Create AI Derivative Profile</h1>
    <form method="POST" action="{{ route('ai-governance.derivatives.store') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-header">Collection</div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="collection_iri" class="form-label">Collection IRI <span class="text-danger">*</span></label>
                    <input type="text" name="collection_iri" id="collection_iri" class="form-control" required>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">Derivative Options</div>
            <div class="card-body">
                <div class="form-check mb-2"><input type="checkbox" name="cleaned_ocr_text" id="cleaned_ocr_text" class="form-check-input" value="1"><label for="cleaned_ocr_text" class="form-check-label">Cleaned OCR Text</label></div>
                <div class="form-check mb-2"><input type="checkbox" name="normalised_metadata_export" id="normalised_metadata_export" class="form-check-input" value="1"><label for="normalised_metadata_export" class="form-check-label">Normalised Metadata Export</label></div>
                <div class="form-check mb-2"><input type="checkbox" name="chunked_retrieval_units" id="chunked_retrieval_units" class="form-check-input" value="1"><label for="chunked_retrieval_units" class="form-check-label">Chunked Retrieval Units</label></div>
                <div class="form-check mb-2"><input type="checkbox" name="redacted_access_copies" id="redacted_access_copies" class="form-check-input" value="1"><label for="redacted_access_copies" class="form-check-label">Redacted Access Copies</label></div>
                <div class="form-check mb-2"><input type="checkbox" name="multilingual_alignment" id="multilingual_alignment" class="form-check-input" value="1"><label for="multilingual_alignment" class="form-check-label">Multilingual Alignment</label></div>
                <div class="row mt-3">
                    <div class="col-md-6 mb-3"><label for="chunk_size" class="form-label">Chunk Size</label><input type="number" name="chunk_size" id="chunk_size" class="form-control" value="512"></div>
                    <div class="col-md-6 mb-3"><label for="chunk_overlap" class="form-label">Chunk Overlap</label><input type="number" name="chunk_overlap" id="chunk_overlap" class="form-control" value="50"></div>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('ai-governance.derivatives.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
