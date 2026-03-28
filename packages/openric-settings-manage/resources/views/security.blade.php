@extends('theme::layouts.1col')

@section('title', 'Security Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-shield-alt me-2"></i>Security</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.security') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="limit_admin_ip" class="form-label">Limit Admin Access by IP</label>
                    <input type="text" name="settings[limit_admin_ip]" id="limit_admin_ip" class="form-control" value="{{ $settings['limit_admin_ip'] ?? '' }}" placeholder="e.g. 192.168.1.0/24, 10.0.0.1">
                    <div class="form-text">Comma-separated list of IP addresses or CIDR ranges allowed to access admin. Leave blank to allow all.</div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[require_ssl_admin]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[require_ssl_admin]" id="require_ssl_admin" value="1" {{ ($settings['require_ssl_admin'] ?? '0') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="require_ssl_admin">Require SSL for Admin Interface</label>
                    <div class="form-text">Forces HTTPS on all admin pages.</div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[require_strong_passwords]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[require_strong_passwords]" id="require_strong_passwords" value="1" {{ ($settings['require_strong_passwords'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="require_strong_passwords">Require Strong Passwords</label>
                    <div class="form-text">Enforce minimum length, mixed case, numbers, and special characters.</div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
