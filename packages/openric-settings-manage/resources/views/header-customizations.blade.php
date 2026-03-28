@extends('theme::layouts.1col')

@section('title', 'Header Customizations')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-heading me-2"></i>Header Customizations</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.header-customizations') }}" enctype="multipart/form-data">
        @csrf
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Colors</h6></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="header_bg_color" class="form-label">Header Background Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[header_bg_color]" id="header_bg_color" class="form-control form-control-color" value="{{ $settings['header_bg_color'] ?? '#212529' }}">
                            <input type="text" class="form-control" value="{{ $settings['header_bg_color'] ?? '#212529' }}" readonly>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="header_text_color" class="form-label">Header Text Color</label>
                        <div class="input-group">
                            <input type="color" name="settings[header_text_color]" id="header_text_color" class="form-control form-control-color" value="{{ $settings['header_text_color'] ?? '#ffffff' }}">
                            <input type="text" class="form-control" value="{{ $settings['header_text_color'] ?? '#ffffff' }}" readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Branding Images</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="header_logo" class="form-label">Logo Image</label>
                    <input type="file" name="header_logo" id="header_logo" class="form-control" accept="image/*">
                    @if (!empty($settings['header_logo']))
                        <div class="mt-2">
                            <img src="{{ asset($settings['header_logo']) }}" alt="Current logo" style="max-height: 60px;">
                            <span class="text-muted ms-2">Current logo</span>
                        </div>
                    @endif
                </div>
                <div class="mb-3">
                    <label for="header_banner" class="form-label">Banner Image</label>
                    <input type="file" name="header_banner" id="header_banner" class="form-control" accept="image/*">
                    @if (!empty($settings['header_banner']))
                        <div class="mt-2">
                            <img src="{{ asset($settings['header_banner']) }}" alt="Current banner" style="max-height: 80px; max-width: 100%;">
                            <span class="text-muted ms-2">Current banner</span>
                        </div>
                    @endif
                </div>
                <div class="mb-3">
                    <label for="header_favicon" class="form-label">Favicon</label>
                    <input type="file" name="header_favicon" id="header_favicon" class="form-control" accept="image/x-icon,image/png">
                    @if (!empty($settings['header_favicon']))
                        <div class="mt-2">
                            <img src="{{ asset($settings['header_favicon']) }}" alt="Current favicon" style="max-height: 32px;">
                            <span class="text-muted ms-2">Current favicon</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
    </form>
</div>
@endsection
