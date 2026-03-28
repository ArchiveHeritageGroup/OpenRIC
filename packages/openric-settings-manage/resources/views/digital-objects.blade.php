@extends('theme::layouts.1col')

@section('title', 'Digital Objects Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-file-image me-2"></i>Digital Objects</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.digital-objects') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="digital_object_max_size" class="form-label">Maximum File Size (MB)</label>
                    <input type="number" name="settings[digital_object_max_size]" id="digital_object_max_size" class="form-control" value="{{ $settings['digital_object_max_size'] ?? 512 }}" min="1">
                    <div class="form-text">Maximum upload size for digital objects in megabytes.</div>
                </div>
                <div class="mb-3">
                    <label for="digital_object_allowed_types" class="form-label">Allowed File Types</label>
                    <input type="text" name="settings[digital_object_allowed_types]" id="digital_object_allowed_types" class="form-control" value="{{ $settings['digital_object_allowed_types'] ?? 'jpg,jpeg,png,gif,tiff,pdf,mp3,mp4,wav,doc,docx,xls,xlsx' }}">
                    <div class="form-text">Comma-separated list of allowed file extensions.</div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
