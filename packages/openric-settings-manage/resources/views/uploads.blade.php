@extends('theme::layouts.1col')

@section('title', 'Upload Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-cloud-upload-alt me-2"></i>Upload Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.uploads') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="upload_max_filesize" class="form-label">Maximum File Size (MB)</label>
                    <input type="number" name="settings[upload_max_filesize]" id="upload_max_filesize" class="form-control" value="{{ $settings['upload_max_filesize'] ?? 256 }}" min="1">
                    <div class="form-text">Note: PHP's upload_max_filesize and post_max_size directives may also impose limits.</div>
                </div>
                <div class="mb-3">
                    <label for="upload_allowed_extensions" class="form-label">Allowed File Extensions</label>
                    <input type="text" name="settings[upload_allowed_extensions]" id="upload_allowed_extensions" class="form-control" value="{{ $settings['upload_allowed_extensions'] ?? 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,csv,xml,zip' }}">
                    <div class="form-text">Comma-separated list of permitted file extensions.</div>
                </div>
                <div class="mb-3">
                    <label for="upload_directory" class="form-label">Upload Directory</label>
                    <input type="text" name="settings[upload_directory]" id="upload_directory" class="form-control" value="{{ $settings['upload_directory'] ?? 'uploads' }}">
                    <div class="form-text">Relative to the storage directory.</div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[upload_generate_derivatives]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[upload_generate_derivatives]" id="upload_generate_derivatives" value="1" {{ ($settings['upload_generate_derivatives'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="upload_generate_derivatives">Auto-generate Derivatives (thumbnails, previews)</label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
