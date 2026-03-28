@extends('theme::layouts.1col')

@section('title', 'Create Record Set')

@section('content')
    <h1 class="h3 mb-4">Create Record Set</h1>

    @include('theme::partials.alerts')

    <form method="POST" action="{{ route('record-sets.store') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="identifier" class="form-label">Identifier</label>
                        <input type="text" class="form-control" id="identifier" name="identifier" value="{{ old('identifier') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="level" class="form-label">Level <span class="text-danger">*</span></label>
                        <select class="form-select @error('level') is-invalid @enderror" id="level" name="level" required>
                            <option value="">Select level...</option>
                            @foreach(['fonds', 'subfonds', 'series', 'subseries', 'file', 'subfile'] as $lvl)
                                <option value="{{ $lvl }}" {{ old('level') === $lvl ? 'selected' : '' }}>{{ ucfirst($lvl) }}</option>
                            @endforeach
                        </select>
                        @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="scope_and_content" class="form-label">Scope and Content</label>
                    <textarea class="form-control" id="scope_and_content" name="scope_and_content" rows="4">{{ old('scope_and_content') }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="date_begin" class="form-label">Date Begin</label>
                        <input type="text" class="form-control" id="date_begin" name="date_begin" placeholder="YYYY or YYYY-MM-DD" value="{{ old('date_begin') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date_end" class="form-label">Date End</label>
                        <input type="text" class="form-control" id="date_end" name="date_end" placeholder="YYYY or YYYY-MM-DD" value="{{ old('date_end') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date_expression" class="form-label">Date Expression</label>
                        <input type="text" class="form-control" id="date_expression" name="date_expression" placeholder="e.g. circa 1940-1960" value="{{ old('date_expression') }}">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="parent_iri" class="form-label">Parent Record Set IRI</label>
                    <input type="text" class="form-control" id="parent_iri" name="parent_iri" value="{{ old('parent_iri') }}" placeholder="https://ric.theahg.co.za/entity/record-set/...">
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Create Record Set</button>
            <a href="{{ route('record-sets.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
