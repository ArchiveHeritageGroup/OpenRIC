@extends('theme::layouts.1col')

@section('title', 'Create Corporate Body — ISAAR-CPF')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Corporate Body <span class="badge bg-secondary">ISAAR-CPF</span></h1>
        @include('theme::partials.view-switch')
    </div>

    @include('theme::partials.alerts')

    <form method="POST" action="{{ route('corporate-bodies.store') }}">
        @csrf
        <input type="hidden" name="_form_type" value="isaar_cpf">

        <div class="card mb-3">
            <div class="card-header bg-light"><h5 class="mb-0">5.1 Identity Area</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">5.1.2 Authorized form of name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="identifier" class="form-label">5.1.6 Identifiers</label>
                    <input type="text" class="form-control" id="identifier" name="identifier" value="{{ old('identifier') }}">
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header bg-light"><h5 class="mb-0">5.2 Description Area</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="date_begin" class="form-label">5.2.1 Dates of existence — Begin</label>
                        <input type="text" class="form-control" id="date_begin" name="date_begin" placeholder="YYYY or YYYY-MM-DD" value="{{ old('date_begin') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="date_end" class="form-label">End</label>
                        <input type="text" class="form-control" id="date_end" name="date_end" placeholder="YYYY or YYYY-MM-DD" value="{{ old('date_end') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="history" class="form-label">5.2.2 History</label>
                    <textarea class="form-control" id="history" name="history" rows="4">{{ old('history') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="places" class="form-label">5.2.3 Places</label>
                    <input type="text" class="form-control" id="places" name="places" value="{{ old('places') }}">
                </div>
                <div class="mb-3">
                    <label for="legal_status" class="form-label">5.2.4 Legal status</label>
                    <input type="text" class="form-control" id="legal_status" name="legal_status" value="{{ old('legal_status') }}">
                </div>
                <div class="mb-3">
                    <label for="functions" class="form-label">5.2.5 Functions, occupations, activities</label>
                    <textarea class="form-control" id="functions" name="functions" rows="2">{{ old('functions') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="mandates" class="form-label">5.2.6 Mandates/sources of authority</label>
                    <textarea class="form-control" id="mandates" name="mandates" rows="2">{{ old('mandates') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="general_context" class="form-label">5.2.8 General context</label>
                    <textarea class="form-control" id="general_context" name="general_context" rows="2">{{ old('general_context') }}</textarea>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Create Corporate Body</button>
            <a href="{{ route('corporate-bodies.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
