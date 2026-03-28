@extends('theme::layouts.1col')

@section('title', 'ICIP Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-id-card me-2"></i>ICIP Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.icip-settings') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[icip_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[icip_enabled]" id="icip_enabled" value="1" {{ ($settings['icip_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="icip_enabled">Enable ICIP Integration</label>
                </div>
                <div class="mb-3">
                    <label for="icip_endpoint" class="form-label">ICIP Endpoint URL</label>
                    <input type="url" name="settings[icip_endpoint]" id="icip_endpoint" class="form-control" value="{{ $settings['icip_endpoint'] ?? '' }}" placeholder="https://icip.example.com/api">
                </div>
                <div class="mb-3">
                    <label for="icip_api_key" class="form-label">API Key</label>
                    <input type="text" name="settings[icip_api_key]" id="icip_api_key" class="form-control" value="{{ $settings['icip_api_key'] ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="icip_institution_code" class="form-label">Institution Code</label>
                    <input type="text" name="settings[icip_institution_code]" id="icip_institution_code" class="form-control" value="{{ $settings['icip_institution_code'] ?? '' }}">
                </div>
                <div class="mb-3">
                    <label for="icip_sync_interval" class="form-label">Sync Interval (minutes)</label>
                    <input type="number" name="settings[icip_sync_interval]" id="icip_sync_interval" class="form-control" value="{{ $settings['icip_sync_interval'] ?? 60 }}" min="5">
                    <div class="form-text">How often to synchronize with the ICIP registry.</div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
