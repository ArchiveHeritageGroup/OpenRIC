@extends('theme::layouts.1col')

@section('title', 'Path Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-folder-open me-2"></i>Path Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.paths') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="path_media" class="form-label">Media Directory</label>
                    <input type="text" name="settings[path_media]" id="path_media" class="form-control" value="{{ $settings['path_media'] ?? '/var/lib/openric/media' }}">
                    <div class="form-text">Absolute path to the media storage directory.</div>
                </div>
                <div class="mb-3">
                    <label for="path_tmp" class="form-label">Temporary Directory</label>
                    <input type="text" name="settings[path_tmp]" id="path_tmp" class="form-control" value="{{ $settings['path_tmp'] ?? '/tmp/openric' }}">
                    <div class="form-text">Directory for temporary file processing.</div>
                </div>
                <div class="mb-3">
                    <label for="path_export" class="form-label">Export Directory</label>
                    <input type="text" name="settings[path_export]" id="path_export" class="form-control" value="{{ $settings['path_export'] ?? '/var/lib/openric/exports' }}">
                    <div class="form-text">Directory where exported files are saved.</div>
                </div>
                <div class="mb-3">
                    <label for="path_backup" class="form-label">Backup Directory</label>
                    <input type="text" name="settings[path_backup]" id="path_backup" class="form-control" value="{{ $settings['path_backup'] ?? '/var/lib/openric/backups' }}">
                    <div class="form-text">Directory for database and file backups.</div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
