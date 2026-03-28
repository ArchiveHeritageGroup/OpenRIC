@extends('theme::layouts.1col')

@section('title', 'Create Record — ISAD(G)')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Record (Item) <span class="badge bg-secondary">ISAD(G)</span></h1>
        @include('theme::partials.view-switch')
    </div>

    @include('theme::partials.alerts')

    <form method="POST" action="{{ route('records.store') }}">
        @csrf
        <input type="hidden" name="_form_type" value="isadg">

        <div class="card mb-3">
            <div class="card-header bg-light"><h5 class="mb-0">3.1 Identity Statement Area</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="identifier" class="form-label">3.1.1 Reference code(s)</label>
                    <input type="text" class="form-control" id="identifier" name="identifier" value="{{ old('identifier') }}">
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">3.1.2 Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date_expression" class="form-label">3.1.3 Date(s)</label>
                        <input type="text" class="form-control" id="date_expression" name="date_expression" value="{{ old('date_expression') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="extent" class="form-label">3.1.5 Extent and medium</label>
                        <input type="text" class="form-control" id="extent" name="extent" value="{{ old('extent') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light"><h5 class="mb-0">3.3 Content and Structure Area</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="scope_and_content" class="form-label">3.3.1 Scope and content</label>
                    <textarea class="form-control" id="scope_and_content" name="scope_and_content" rows="4">{{ old('scope_and_content') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light"><h5 class="mb-0">3.4 Conditions of Access and Use</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="access_conditions" class="form-label">3.4.1 Conditions governing access</label>
                    <textarea class="form-control" id="access_conditions" name="access_conditions" rows="2">{{ old('access_conditions') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="language" class="form-label">3.4.3 Language/scripts</label>
                    <input type="text" class="form-control" id="language" name="language" value="{{ old('language') }}">
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="parent_iri" class="form-label">Parent (File/Series IRI)</label>
            <input type="text" class="form-control" id="parent_iri" name="parent_iri" value="{{ old('parent_iri') }}">
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Create Record</button>
            <a href="{{ route('records.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
