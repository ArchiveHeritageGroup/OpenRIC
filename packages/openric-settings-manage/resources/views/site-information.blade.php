@extends('theme::layouts.1col')

@section('title', 'Site Information Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-info-circle me-2"></i>Site Information</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.site-information') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="siteTitle" class="form-label">Site Title</label>
                    <input type="text" name="settings[siteTitle]" id="siteTitle" class="form-control" value="{{ $settings['siteTitle'] ?? '' }}">
                    <div class="form-text">The title displayed in the browser tab and header.</div>
                </div>
                <div class="mb-3">
                    <label for="siteDescription" class="form-label">Site Description</label>
                    <textarea name="settings[siteDescription]" id="siteDescription" class="form-control" rows="3">{{ $settings['siteDescription'] ?? '' }}</textarea>
                    <div class="form-text">A brief description of your archive or institution.</div>
                </div>
                <div class="mb-3">
                    <label for="siteBaseUrl" class="form-label">Site Base URL</label>
                    <input type="url" name="settings[siteBaseUrl]" id="siteBaseUrl" class="form-control" value="{{ $settings['siteBaseUrl'] ?? '' }}" placeholder="https://example.com">
                    <div class="form-text">The public-facing URL for this installation.</div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
