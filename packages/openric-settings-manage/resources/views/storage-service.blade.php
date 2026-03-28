@extends('theme::layouts.1col')

@section('title', 'Storage Service Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-hdd me-2"></i>Storage Service</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.storage-service') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="storage_driver" class="form-label">Storage Driver</label>
                    <select name="settings[storage_driver]" id="storage_driver" class="form-select">
                        <option value="local" {{ ($settings['storage_driver'] ?? 'local') == 'local' ? 'selected' : '' }}>Local Filesystem</option>
                        <option value="s3" {{ ($settings['storage_driver'] ?? '') == 's3' ? 'selected' : '' }}>Amazon S3</option>
                        <option value="minio" {{ ($settings['storage_driver'] ?? '') == 'minio' ? 'selected' : '' }}>MinIO</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="storage_endpoint" class="form-label">Endpoint URL</label>
                    <input type="url" name="settings[storage_endpoint]" id="storage_endpoint" class="form-control" value="{{ $settings['storage_endpoint'] ?? '' }}" placeholder="https://s3.amazonaws.com">
                    <div class="form-text">Required for S3/MinIO. Leave blank for local storage.</div>
                </div>
                <div class="mb-3">
                    <label for="storage_bucket" class="form-label">Bucket Name</label>
                    <input type="text" name="settings[storage_bucket]" id="storage_bucket" class="form-control" value="{{ $settings['storage_bucket'] ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="storage_access_key" class="form-label">Access Key</label>
                    <input type="text" name="settings[storage_access_key]" id="storage_access_key" class="form-control" value="{{ $settings['storage_access_key'] ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="storage_secret_key" class="form-label">Secret Key</label>
                    <input type="password" name="settings[storage_secret_key]" id="storage_secret_key" class="form-control" value="{{ $settings['storage_secret_key'] ?? '' }}">
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
