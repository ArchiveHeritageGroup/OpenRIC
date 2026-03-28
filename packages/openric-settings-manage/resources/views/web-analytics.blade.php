@extends('theme::layouts.1col')

@section('title', 'Web Analytics Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-chart-line me-2"></i>Web Analytics</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.web-analytics') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="analytics_tracking_id" class="form-label">Google Analytics Tracking ID</label>
                    <input type="text" name="settings[analytics_tracking_id]" id="analytics_tracking_id" class="form-control" value="{{ $settings['analytics_tracking_id'] ?? '' }}" placeholder="G-XXXXXXXXXX or UA-XXXXXXXX-X">
                    <div class="form-text">Enter your Google Analytics measurement ID. Leave blank to disable.</div>
                </div>
                <div class="mb-3">
                    <label for="analytics_custom_script" class="form-label">Custom Analytics Script</label>
                    <textarea name="settings[analytics_custom_script]" id="analytics_custom_script" class="form-control font-monospace" rows="6">{{ $settings['analytics_custom_script'] ?? '' }}</textarea>
                    <div class="form-text">Paste any additional analytics or tracking JavaScript here. It will be injected into the page head.</div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
