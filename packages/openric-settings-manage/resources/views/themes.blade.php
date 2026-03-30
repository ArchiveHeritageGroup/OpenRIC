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
                        <label for="primary_color" class="form-label">Primary Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[primary_color]" id="primary_color" class="form-control form-control-color" value="{{ $settings['primary_color'] ?? '#1a5276' }}">
                            <input type="text" class="form-control" value="{{ $settings['primary_color'] ?? '#1a5276' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="secondary_color" class="form-label">Secondary Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[secondary_color]" id="secondary_color" class="form-control form-control-color" value="{{ $settings['secondary_color'] ?? '#6c757d' }}">
                            <input type="text" class="form-control" value="{{ $settings['secondary_color'] ?? '#6c757d' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="header_bg" class="form-label">Header Background</label>
                        <div class="input-group">
                            <input type="color" name="settings[header_bg]" id="header_bg" class="form-control form-control-color" value="{{ $settings['header_bg'] ?? '#212529' }}">
                            <input type="text" class="form-control" value="{{ $settings['header_bg'] ?? '#212529' }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="footer_bg" class="form-label">Footer Background</label>
                        <div class="input-group">
                            <input type="color" name="settings[footer_bg]" id="footer_bg" class="form-control form-control-color" value="{{ $settings['footer_bg'] ?? '#212529' }}">
                            <input type="text" class="form-control" value="{{ $settings['footer_bg'] ?? '#212529' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="sidebar_bg" class="form-label">Sidebar Background</label>
                        <div class="input-group">
                            <input type="color" name="settings[sidebar_bg]" id="sidebar_bg" class="form-control form-control-color" value="{{ $settings['sidebar_bg'] ?? '#f8f9fa' }}">
                            <input type="text" class="form-control" value="{{ $settings['sidebar_bg'] ?? '#f8f9fa' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="link_color" class="form-label">Link Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[link_color]" id="link_color" class="form-control form-control-color" value="{{ $settings['link_color'] ?? '#1a5276' }}">
                            <input type="text" class="form-control" value="{{ $settings['link_color'] ?? '#1a5276' }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="success_color" class="form-label">Success Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[success_color]" id="success_color" class="form-control form-control-color" value="{{ $settings['success_color'] ?? '#28a745' }}">
                            <input type="text" class="form-control" value="{{ $settings['success_color'] ?? '#28a745' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="danger_color" class="form-label">Danger Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[danger_color]" id="danger_color" class="form-control form-control-color" value="{{ $settings['danger_color'] ?? '#dc3545' }}">
                            <input type="text" class="form-control" value="{{ $settings['danger_color'] ?? '#dc3545' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="warning_color" class="form-label">Warning Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[warning_color]" id="warning_color" class="form-control form-control-color" value="{{ $settings['warning_color'] ?? '#ffc107' }}">
                            <input type="text" class="form-control" value="{{ $settings['warning_color'] ?? '#ffc107' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="info_color" class="form-label">Info Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[info_color]" id="info_color" class="form-control form-control-color" value="{{ $settings['info_color'] ?? '#17a2b8' }}">
                            <input type="text" class="form-control" value="{{ $settings['info_color'] ?? '#17a2b8' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Text Colors</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="header_text" class="form-label">Header Text</label>
                        <div class="input-group">
                            <input type="color" name="settings[header_text]" id="header_text" class="form-control form-control-color" value="{{ $settings['header_text'] ?? '#ffffff' }}">
                            <input type="text" class="form-control" value="{{ $settings['header_text'] ?? '#ffffff' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="footer_text_color" class="form-label">Footer Text</label>
                        <div class="input-group">
                            <input type="color" name="settings[footer_text_color]" id="footer_text_color" class="form-control form-control-color" value="{{ $settings['footer_text_color'] ?? '#ffffff' }}">
                            <input type="text" class="form-control" value="{{ $settings['footer_text_color'] ?? '#ffffff' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="body_text" class="form-label">Body Text</label>
                        <div class="input-group">
                            <input type="color" name="settings[body_text]" id="body_text" class="form-control form-control-color" value="{{ $settings['body_text'] ?? '#212529' }}">
                            <input type="text" class="form-control" value="{{ $settings['body_text'] ?? '#212529' }}" readonly>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="sidebar_text" class="form-label">Sidebar Text</label>
                        <div class="input-group">
                            <input type="color" name="settings[sidebar_text]" id="sidebar_text" class="form-control form-control-color" value="{{ $settings['sidebar_text'] ?? '#333333' }}">
                            <input type="text" class="form-control" value="{{ $settings['sidebar_text'] ?? '#333333' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="body_bg" class="form-label">Body Background</label>
                        <div class="input-group">
                            <input type="color" name="settings[body_bg]" id="body_bg" class="form-control form-control-color" value="{{ $settings['body_bg'] ?? '#ffffff' }}">
                            <input type="text" class="form-control" value="{{ $settings['body_bg'] ?? '#ffffff' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Custom CSS</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="custom_css" class="form-label">Custom CSS</label>
                    <textarea name="settings[custom_css]" id="custom_css" class="form-control font-monospace" rows="8">{{ $settings['custom_css'] ?? '' }}</textarea>
                    <div class="form-text">Additional CSS appended to every page.</div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
    </form>
</div>
@endsection
