@extends('theme::layouts.1col')

@section('title', 'Default Template Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-file-alt me-2"></i>Default Templates</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.default-template') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-3">Select the default display template for each entity type.</p>

                @foreach ($settings['templates'] ?? [] as $entityType => $template)
                <div class="mb-3">
                    <label for="template_{{ $entityType }}" class="form-label">{{ ucfirst(str_replace('_', ' ', $entityType)) }}</label>
                    <select name="templates[{{ $entityType }}]" id="template_{{ $entityType }}" class="form-select">
                        @foreach ($template['options'] ?? [] as $value => $label)
                        <option value="{{ $value }}" {{ ($template['selected'] ?? '') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @endforeach

                @if (empty($settings['templates'] ?? []))
                <p class="text-muted">No template configurations available.</p>
                @endif
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
