@extends('theme::layouts.1col')

@section('title', 'Theme Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-palette me-2"></i>Theme Configuration</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.themes') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Color Scheme</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="theme_primary_color" class="form-label">Primary Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[theme_primary_color]" id="theme_primary_color" class="form-control form-control-color" value="{{ $settings['theme_primary_color'] ?? '#0d6efd' }}">
                            <input type="text" class="form-control" value="{{ $settings['theme_primary_color'] ?? '#0d6efd' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="theme_secondary_color" class="form-label">Secondary Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[theme_secondary_color]" id="theme_secondary_color" class="form-control form-control-color" value="{{ $settings['theme_secondary_color'] ?? '#6c757d' }}">
                            <input type="text" class="form-control" value="{{ $settings['theme_secondary_color'] ?? '#6c757d' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="theme_accent_color" class="form-label">Accent Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[theme_accent_color]" id="theme_accent_color" class="form-control form-control-color" value="{{ $settings['theme_accent_color'] ?? '#198754' }}">
                            <input type="text" class="form-control" value="{{ $settings['theme_accent_color'] ?? '#198754' }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="theme_header_bg" class="form-label">Header Background</label>
                        <div class="input-group">
                            <input type="color" name="settings[theme_header_bg]" id="theme_header_bg" class="form-control form-control-color" value="{{ $settings['theme_header_bg'] ?? '#212529' }}">
                            <input type="text" class="form-control" value="{{ $settings['theme_header_bg'] ?? '#212529' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="theme_footer_bg" class="form-label">Footer Background</label>
                        <div class="input-group">
                            <input type="color" name="settings[theme_footer_bg]" id="theme_footer_bg" class="form-control form-control-color" value="{{ $settings['theme_footer_bg'] ?? '#212529' }}">
                            <input type="text" class="form-control" value="{{ $settings['theme_footer_bg'] ?? '#212529' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="theme_sidebar_bg" class="form-label">Sidebar Background</label>
                        <div class="input-group">
                            <input type="color" name="settings[theme_sidebar_bg]" id="theme_sidebar_bg" class="form-control form-control-color" value="{{ $settings['theme_sidebar_bg'] ?? '#f8f9fa' }}">
                            <input type="text" class="form-control" value="{{ $settings['theme_sidebar_bg'] ?? '#f8f9fa' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Typography &amp; Layout</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="theme_font_family" class="form-label">Font Family</label>
                        <input type="text" name="settings[theme_font_family]" id="theme_font_family" class="form-control" value="{{ $settings['theme_font_family'] ?? 'system-ui, -apple-system, sans-serif' }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="theme_base_font_size" class="form-label">Base Font Size</label>
                        <input type="text" name="settings[theme_base_font_size]" id="theme_base_font_size" class="form-control" value="{{ $settings['theme_base_font_size'] ?? '16px' }}">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="theme_border_radius" class="form-label">Border Radius</label>
                        <input type="text" name="settings[theme_border_radius]" id="theme_border_radius" class="form-control" value="{{ $settings['theme_border_radius'] ?? '0.375rem' }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="theme_custom_css" class="form-label">Custom CSS</label>
                    <textarea name="settings[theme_custom_css]" id="theme_custom_css" class="form-control font-monospace" rows="6">{{ $settings['theme_custom_css'] ?? '' }}</textarea>
                    <div class="form-text">Additional CSS appended to every page.</div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
    </form>
</div>
@endsection
