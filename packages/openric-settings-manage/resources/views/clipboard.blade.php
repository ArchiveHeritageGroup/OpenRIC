@extends('theme::layouts.1col')

@section('title', 'Clipboard Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-clipboard me-2"></i>Clipboard Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.clipboard') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[clipboard_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[clipboard_enabled]" id="clipboard_enabled" value="1" {{ ($settings['clipboard_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="clipboard_enabled">Enable Clipboard Feature</label>
                    <div class="form-text">Allow users to add records to a clipboard for batch operations.</div>
                </div>
                <div class="mb-3">
                    <label for="clipboard_max_items" class="form-label">Maximum Clipboard Items</label>
                    <input type="number" name="settings[clipboard_max_items]" id="clipboard_max_items" class="form-control" value="{{ $settings['clipboard_max_items'] ?? 500 }}" min="10" max="10000">
                </div>
                <div class="form-check form-switch mb-3">
                    <input type="hidden" name="settings[clipboard_persist_session]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[clipboard_persist_session]" id="clipboard_persist_session" value="1" {{ ($settings['clipboard_persist_session'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="clipboard_persist_session">Persist Clipboard Across Sessions</label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
