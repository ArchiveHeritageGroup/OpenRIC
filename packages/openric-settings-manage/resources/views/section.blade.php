@extends('theme::layouts.1col')

@section('title', $sectionLabel . ' Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-sliders-h me-2"></i>{{ $sectionLabel }}</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <form method="POST" action="{{ route('settings.section', $section) }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @foreach ($settings as $setting)
                <div class="mb-3">
                    <label for="setting_{{ $setting->id }}" class="form-label">{{ $setting->name }}</label>
                    <input type="text" name="settings[{{ $setting->id }}]" id="setting_{{ $setting->id }}" class="form-control" value="{{ $setting->value ?? '' }}">
                </div>
                @endforeach
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
