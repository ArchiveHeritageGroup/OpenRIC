@extends('theme::layouts.1col')

@section('title', 'Finding Aid Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-book-open me-2"></i>Finding Aid</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.finding-aid') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[finding_aid_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[finding_aid_enabled]" id="finding_aid_enabled" value="1" {{ ($settings['finding_aid_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="finding_aid_enabled">Enable Finding Aid Generation</label>
                </div>
                <div class="mb-3">
                    <label for="finding_aid_format" class="form-label">Default Export Format</label>
                    <select name="settings[finding_aid_format]" id="finding_aid_format" class="form-select">
                        <option value="ead" {{ ($settings['finding_aid_format'] ?? 'ead') == 'ead' ? 'selected' : '' }}>EAD (Encoded Archival Description)</option>
                        <option value="ead3" {{ ($settings['finding_aid_format'] ?? '') == 'ead3' ? 'selected' : '' }}>EAD3</option>
                        <option value="pdf" {{ ($settings['finding_aid_format'] ?? '') == 'pdf' ? 'selected' : '' }}>PDF</option>
                        <option value="html" {{ ($settings['finding_aid_format'] ?? '') == 'html' ? 'selected' : '' }}>HTML</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="finding_aid_institution" class="form-label">Institution Name (for header)</label>
                    <input type="text" name="settings[finding_aid_institution]" id="finding_aid_institution" class="form-control" value="{{ $settings['finding_aid_institution'] ?? '' }}">
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[finding_aid_include_digital_objects]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[finding_aid_include_digital_objects]" id="finding_aid_include_digital_objects" value="1" {{ ($settings['finding_aid_include_digital_objects'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="finding_aid_include_digital_objects">Include Digital Object References</label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
