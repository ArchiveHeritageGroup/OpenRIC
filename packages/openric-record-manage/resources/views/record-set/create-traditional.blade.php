@extends('theme::layouts.1col')

@section('title', 'Create Record Set — ISAD(G)')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Record Set <span class="badge bg-secondary">ISAD(G)</span></h1>
        @include('theme::partials.view-switch')
    </div>

    @include('theme::partials.alerts')

    <form method="POST" action="{{ route('record-sets.store') }}">
        @csrf
        <input type="hidden" name="_form_type" value="isadg">

        {{-- 3.1 Identity Statement Area --}}
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
                    <div class="col-md-4 mb-3">
                        <label for="date_begin" class="form-label">3.1.3 Date(s) — Begin</label>
                        <input type="text" class="form-control" id="date_begin" name="date_begin" placeholder="YYYY or YYYY-MM-DD" value="{{ old('date_begin') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date_end" class="form-label">End</label>
                        <input type="text" class="form-control" id="date_end" name="date_end" placeholder="YYYY or YYYY-MM-DD" value="{{ old('date_end') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="date_expression" class="form-label">Expression</label>
                        <input type="text" class="form-control" id="date_expression" name="date_expression" value="{{ old('date_expression') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="level" class="form-label">3.1.4 Level of description <span class="text-danger">*</span></label>
                        <select class="form-select @error('level') is-invalid @enderror" id="level" name="level" required>
                            <option value="">Select...</option>
                            @foreach(['fonds', 'subfonds', 'series', 'subseries', 'file', 'subfile'] as $lvl)
                                <option value="{{ $lvl }}" {{ old('level') === $lvl ? 'selected' : '' }}>{{ ucfirst($lvl) }}</option>
                            @endforeach
                        </select>
                        @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="extent" class="form-label">3.1.5 Extent and medium</label>
                        <input type="text" class="form-control" id="extent" name="extent" value="{{ old('extent') }}">
                    </div>
                </div>
            </div>
        </div>

        {{-- 3.2 Context Area --}}
        <div class="card mb-3">
            <div class="card-header bg-light"><h5 class="mb-0">3.2 Context Area</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="admin_history" class="form-label">3.2.2 Administrative/biographical history</label>
                    <textarea class="form-control" id="admin_history" name="admin_history" rows="3">{{ old('admin_history') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="archival_history" class="form-label">3.2.3 Archival history</label>
                    <textarea class="form-control" id="archival_history" name="archival_history" rows="2">{{ old('archival_history') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="acquisition" class="form-label">3.2.4 Immediate source of acquisition</label>
                    <textarea class="form-control" id="acquisition" name="acquisition" rows="2">{{ old('acquisition') }}</textarea>
                </div>
            </div>
        </div>

        {{-- 3.3 Content and Structure Area --}}
        <div class="card mb-3">
            <div class="card-header bg-light"><h5 class="mb-0">3.3 Content and Structure Area</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="scope_and_content" class="form-label">3.3.1 Scope and content</label>
                    <textarea class="form-control" id="scope_and_content" name="scope_and_content" rows="4">{{ old('scope_and_content') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="arrangement" class="form-label">3.3.4 System of arrangement</label>
                    <textarea class="form-control" id="arrangement" name="arrangement" rows="2">{{ old('arrangement') }}</textarea>
                </div>
            </div>
        </div>

        {{-- 3.4 Conditions of Access and Use Area --}}
        <div class="card mb-3">
            <div class="card-header bg-light"><h5 class="mb-0">3.4 Conditions of Access and Use</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="access_conditions" class="form-label">3.4.1 Conditions governing access</label>
                    <textarea class="form-control" id="access_conditions" name="access_conditions" rows="2">{{ old('access_conditions') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="reproduction_conditions" class="form-label">3.4.2 Conditions governing reproduction</label>
                    <textarea class="form-control" id="reproduction_conditions" name="reproduction_conditions" rows="2">{{ old('reproduction_conditions') }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="language" class="form-label">3.4.3 Language/scripts of material</label>
                    <input type="text" class="form-control" id="language" name="language" value="{{ old('language') }}">
                </div>
            </div>
        </div>

        {{-- 3.6 Notes Area --}}
        <div class="card mb-3">
            <div class="card-header bg-light"><h5 class="mb-0">3.6 Notes</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="note" class="form-label">3.6.1 Note</label>
                    <textarea class="form-control" id="note" name="note" rows="2">{{ old('note') }}</textarea>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label for="parent_iri" class="form-label">Parent Record Set</label>
            <input type="text" class="form-control" id="parent_iri" name="parent_iri" value="{{ old('parent_iri') }}" placeholder="IRI of parent record set">
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Create Record Set</button>
            <a href="{{ route('record-sets.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
