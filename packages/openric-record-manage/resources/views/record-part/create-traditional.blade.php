@extends('theme::layouts.1col')

@section('title', 'Create Record Part — ISAD(G)')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Record Part <span class="badge bg-secondary">ISAD(G)</span></h1>
        @include('theme::partials.view-switch')
    </div>

    @include('theme::partials.alerts')

    <form method="POST" action="{{ route('record-parts.store') }}">
        @csrf
        <input type="hidden" name="_form_type" value="isadg">

        <div class="card mb-3">
            <div class="card-body">
                <div class="mb-3">
                    <label for="identifier" class="form-label">3.1.1 Reference code</label>
                    <input type="text" class="form-control" id="identifier" name="identifier" value="{{ old('identifier') }}">
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">3.1.2 Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="parent_iri" class="form-label">Parent Record IRI</label>
                    <input type="text" class="form-control" id="parent_iri" name="parent_iri" value="{{ old('parent_iri') }}">
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Create Record Part</button>
            <a href="{{ route('record-parts.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
