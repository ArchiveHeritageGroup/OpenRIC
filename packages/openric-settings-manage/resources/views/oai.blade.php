@extends('theme::layouts.1col')

@section('title', 'OAI-PMH Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-exchange-alt me-2"></i>OAI-PMH Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.oai') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[oai_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[oai_enabled]" id="oai_enabled" value="1" {{ ($settings['oai_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="oai_enabled">Enable OAI-PMH Provider</label>
                </div>
                <div class="mb-3">
                    <label for="oai_repository_name" class="form-label">Repository Name</label>
                    <input type="text" name="settings[oai_repository_name]" id="oai_repository_name" class="form-control" value="{{ $settings['oai_repository_name'] ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="oai_admin_email" class="form-label">Admin Email</label>
                    <input type="email" name="settings[oai_admin_email]" id="oai_admin_email" class="form-control" value="{{ $settings['oai_admin_email'] ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="oai_base_url" class="form-label">OAI Base URL</label>
                    <input type="url" name="settings[oai_base_url]" id="oai_base_url" class="form-control" value="{{ $settings['oai_base_url'] ?? '' }}" placeholder="https://example.com/oai">
                </div>
                <div class="mb-3">
                    <label for="oai_repository_identifier" class="form-label">Repository Identifier</label>
                    <input type="text" name="settings[oai_repository_identifier]" id="oai_repository_identifier" class="form-control" value="{{ $settings['oai_repository_identifier'] ?? '' }}" placeholder="e.g. oai:example.com">
                </div>
                <div class="mb-3">
                    <label for="oai_results_per_page" class="form-label">Results Per Page</label>
                    <input type="number" name="settings[oai_results_per_page]" id="oai_results_per_page" class="form-control" value="{{ $settings['oai_results_per_page'] ?? 100 }}" min="10" max="1000">
                </div>
                <div class="mb-3">
                    <label for="oai_metadata_formats" class="form-label">Metadata Formats</label>
                    <input type="text" name="settings[oai_metadata_formats]" id="oai_metadata_formats" class="form-control" value="{{ $settings['oai_metadata_formats'] ?? 'oai_dc,ead,mods' }}">
                    <div class="form-text">Comma-separated list of supported metadata formats.</div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
