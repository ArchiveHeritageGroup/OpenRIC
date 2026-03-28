@extends('theme::layouts.1col')

@section('title', 'Email Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-envelope me-2"></i>Email Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.email') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">SMTP Configuration</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="smtp_host" class="form-label">SMTP Host</label>
                        <input type="text" name="settings[smtp_host]" id="smtp_host" class="form-control" value="{{ $settings['smtp_host'] ?? '' }}" placeholder="smtp.example.com">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="smtp_port" class="form-label">SMTP Port</label>
                        <input type="number" name="settings[smtp_port]" id="smtp_port" class="form-control" value="{{ $settings['smtp_port'] ?? 587 }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="smtp_username" class="form-label">SMTP Username</label>
                        <input type="text" name="settings[smtp_username]" id="smtp_username" class="form-control" value="{{ $settings['smtp_username'] ?? '' }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="smtp_password" class="form-label">SMTP Password</label>
                        <input type="password" name="settings[smtp_password]" id="smtp_password" class="form-control" value="{{ $settings['smtp_password'] ?? '' }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="smtp_encryption" class="form-label">Encryption</label>
                    <select name="settings[smtp_encryption]" id="smtp_encryption" class="form-select">
                        <option value="tls" {{ ($settings['smtp_encryption'] ?? 'tls') == 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="ssl" {{ ($settings['smtp_encryption'] ?? '') == 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="" {{ ($settings['smtp_encryption'] ?? 'tls') == '' ? 'selected' : '' }}>None</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Sender &amp; Notifications</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="mail_from_address" class="form-label">From Address</label>
                        <input type="email" name="settings[mail_from_address]" id="mail_from_address" class="form-control" value="{{ $settings['mail_from_address'] ?? '' }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="mail_from_name" class="form-label">From Name</label>
                        <input type="text" name="settings[mail_from_name]" id="mail_from_name" class="form-control" value="{{ $settings['mail_from_name'] ?? '' }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="notification_recipients" class="form-label">Notification Recipients</label>
                    <input type="text" name="settings[notification_recipients]" id="notification_recipients" class="form-control" value="{{ $settings['notification_recipients'] ?? '' }}" placeholder="admin@example.com, staff@example.com">
                    <div class="form-text">Comma-separated email addresses for system notifications.</div>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[enable_email_notifications]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[enable_email_notifications]" id="enable_email_notifications" value="1" {{ ($settings['enable_email_notifications'] ?? '0') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="enable_email_notifications">Enable Email Notifications</label>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Email Templates</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="email_header_template" class="form-label">Header Template (HTML)</label>
                    <textarea name="settings[email_header_template]" id="email_header_template" class="form-control" rows="4">{{ $settings['email_header_template'] ?? '' }}</textarea>
                </div>
                <div class="mb-3">
                    <label for="email_footer_template" class="form-label">Footer Template (HTML)</label>
                    <textarea name="settings[email_footer_template]" id="email_footer_template" class="form-control" rows="4">{{ $settings['email_footer_template'] ?? '' }}</textarea>
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[email_use_html]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[email_use_html]" id="email_use_html" value="1" {{ ($settings['email_use_html'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="email_use_html">Send HTML Emails</label>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
    </form>
</div>
@endsection
