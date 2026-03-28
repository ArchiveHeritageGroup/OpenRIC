@extends('theme::layouts.1col')

@section('title', 'DIP Upload Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-upload me-2"></i>DIP Upload</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.dip-upload') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-check form-switch">
                    <input type="hidden" name="settings[dip_upload_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[dip_upload_enabled]" id="dip_upload_enabled" value="1" {{ ($settings['dip_upload_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="dip_upload_enabled">Enable DIP Upload from Archivematica</label>
                    <div class="form-text">Accept Dissemination Information Packages (DIPs) from Archivematica for automatic ingest.</div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
