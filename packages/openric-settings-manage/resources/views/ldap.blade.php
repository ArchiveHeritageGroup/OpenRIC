@extends('theme::layouts.1col')

@section('title', 'LDAP Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-address-book me-2"></i>LDAP Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.ldap') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[ldap_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[ldap_enabled]" id="ldap_enabled" value="1" {{ ($settings['ldap_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="ldap_enabled">Enable LDAP Authentication</label>
                </div>
                <div class="mb-3">
                    <label for="ldap_host" class="form-label">LDAP Server Host</label>
                    <input type="text" name="settings[ldap_host]" id="ldap_host" class="form-control" value="{{ $settings['ldap_host'] ?? '' }}" placeholder="ldap.example.com">
                </div>
                <div class="mb-3">
                    <label for="ldap_port" class="form-label">LDAP Port</label>
                    <input type="number" name="settings[ldap_port]" id="ldap_port" class="form-control" value="{{ $settings['ldap_port'] ?? 389 }}">
                </div>
                <div class="mb-3">
                    <label for="ldap_base_dn" class="form-label">Base DN</label>
                    <input type="text" name="settings[ldap_base_dn]" id="ldap_base_dn" class="form-control" value="{{ $settings['ldap_base_dn'] ?? '' }}" placeholder="dc=example,dc=com">
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
