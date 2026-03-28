@extends('theme::layouts.1col')

@section('title', 'Privacy Notification Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-user-shield me-2"></i>Privacy Notification</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.privacy-notification') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[privacy_notification_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[privacy_notification_enabled]" id="privacy_notification_enabled" value="1" {{ ($settings['privacy_notification_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="privacy_notification_enabled">Enable Privacy Notification Banner</label>
                </div>
                <div class="mb-3">
                    <label for="privacy_notification_text" class="form-label">Notification Text</label>
                    <textarea name="settings[privacy_notification_text]" id="privacy_notification_text" class="form-control" rows="4">{{ $settings['privacy_notification_text'] ?? 'This site uses cookies to enhance your experience. By continuing to browse, you agree to our privacy policy.' }}</textarea>
                    <div class="form-text">HTML is allowed. This text appears in the cookie/privacy banner shown to visitors.</div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
