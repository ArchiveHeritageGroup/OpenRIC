@extends('theme::layouts.1col')

@section('title', 'Identifier Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-fingerprint me-2"></i>Identifier Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.identifier') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="id_prefix" class="form-label">Identifier Prefix</label>
                    <input type="text" name="settings[id_prefix]" id="id_prefix" class="form-control" value="{{ $settings['id_prefix'] ?? '' }}" placeholder="e.g. OR-">
                    <div class="form-text">Prefix applied to all generated identifiers.</div>
                </div>
                <div class="mb-3">
                    <label for="id_separator" class="form-label">Identifier Separator</label>
                    <input type="text" name="settings[id_separator]" id="id_separator" class="form-control" value="{{ $settings['id_separator'] ?? '-' }}" maxlength="5">
                </div>
                <div class="mb-3">
                    <label for="id_min_digits" class="form-label">Minimum Digits</label>
                    <input type="number" name="settings[id_min_digits]" id="id_min_digits" class="form-control" value="{{ $settings['id_min_digits'] ?? 4 }}" min="1" max="20">
                </div>
                <div class="mb-3">
                    <label for="id_next_number" class="form-label">Next Auto Number</label>
                    <input type="number" name="settings[id_next_number]" id="id_next_number" class="form-control" value="{{ $settings['id_next_number'] ?? 1 }}" min="1">
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[id_auto_generate]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[id_auto_generate]" id="id_auto_generate" value="1" {{ ($settings['id_auto_generate'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="id_auto_generate">Auto-generate Identifiers</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[id_allow_duplicate]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[id_allow_duplicate]" id="id_allow_duplicate" value="1" {{ ($settings['id_allow_duplicate'] ?? '0') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="id_allow_duplicate">Allow Duplicate Identifiers</label>
                </div>
                <div class="mb-3">
                    <label for="id_format_template" class="form-label">Format Template</label>
                    <input type="text" name="settings[id_format_template]" id="id_format_template" class="form-control" value="{{ $settings['id_format_template'] ?? '{prefix}{separator}{number}' }}">
                    <div class="form-text">Available tokens: {prefix}, {separator}, {number}, {year}, {type}</div>
                </div>
                <div class="mb-3">
                    <label for="id_scope" class="form-label">Identifier Scope</label>
                    <select name="settings[id_scope]" id="id_scope" class="form-select">
                        <option value="global" {{ ($settings['id_scope'] ?? 'global') == 'global' ? 'selected' : '' }}>Global (shared sequence)</option>
                        <option value="per_type" {{ ($settings['id_scope'] ?? '') == 'per_type' ? 'selected' : '' }}>Per Entity Type</option>
                        <option value="per_collection" {{ ($settings['id_scope'] ?? '') == 'per_collection' ? 'selected' : '' }}>Per Collection</option>
                    </select>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[id_include_year]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[id_include_year]" id="id_include_year" value="1" {{ ($settings['id_include_year'] ?? '0') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="id_include_year">Include Year in Identifier</label>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[id_editable_after_creation]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[id_editable_after_creation]" id="id_editable_after_creation" value="1" {{ ($settings['id_editable_after_creation'] ?? '0') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="id_editable_after_creation">Allow Editing After Creation</label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
