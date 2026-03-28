@extends('theme::layouts.1col')

@section('title', $groupLabel . ' Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas {{ $groupIcon }} me-2"></i>{{ $groupLabel }}</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <form method="POST" action="{{ route('settings.openric', $group) }}">
        @csrf
        <div class="card">
            <div class="card-body">
                @foreach ($settings as $setting)
                <div class="mb-3">
                    @if (in_array($setting->setting_key, $checkboxFields))
                        <div class="form-check form-switch">
                            <input type="hidden" name="settings[{{ $setting->setting_key }}]" value="0">
                            <input class="form-check-input" type="checkbox" name="settings[{{ $setting->setting_key }}]" id="s_{{ $setting->setting_key }}" value="1" {{ $setting->setting_value === '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="s_{{ $setting->setting_key }}">{{ $setting->description ?: str_replace('_', ' ', ucfirst($setting->setting_key)) }}</label>
                        </div>
                    @elseif (isset($selectFields[$setting->setting_key]))
                        <label for="s_{{ $setting->setting_key }}" class="form-label">{{ $setting->description ?: str_replace('_', ' ', ucfirst($setting->setting_key)) }}</label>
                        <select name="settings[{{ $setting->setting_key }}]" id="s_{{ $setting->setting_key }}" class="form-select">
                            @foreach ($selectFields[$setting->setting_key] as $val => $lbl)
                                <option value="{{ $val }}" {{ $setting->setting_value === (string) $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    @elseif (in_array($setting->setting_key, $colorFields))
                        <label for="s_{{ $setting->setting_key }}" class="form-label">{{ $setting->description ?: str_replace('_', ' ', ucfirst($setting->setting_key)) }}</label>
                        <div class="input-group">
                            <input type="color" name="settings[{{ $setting->setting_key }}]" id="s_{{ $setting->setting_key }}" class="form-control form-control-color" value="{{ $setting->setting_value ?: '#000000' }}">
                            <input type="text" class="form-control" value="{{ $setting->setting_value }}" readonly>
                        </div>
                    @elseif (in_array($setting->setting_key, $passwordFields))
                        <label for="s_{{ $setting->setting_key }}" class="form-label">{{ $setting->description ?: str_replace('_', ' ', ucfirst($setting->setting_key)) }}</label>
                        <input type="password" name="settings[{{ $setting->setting_key }}]" id="s_{{ $setting->setting_key }}" class="form-control" value="{{ $setting->setting_value }}">
                    @elseif (in_array($setting->setting_key, $textareaFields))
                        <label for="s_{{ $setting->setting_key }}" class="form-label">{{ $setting->description ?: str_replace('_', ' ', ucfirst($setting->setting_key)) }}</label>
                        <textarea name="settings[{{ $setting->setting_key }}]" id="s_{{ $setting->setting_key }}" class="form-control font-monospace" rows="6">{{ $setting->setting_value }}</textarea>
                    @else
                        <label for="s_{{ $setting->setting_key }}" class="form-label">{{ $setting->description ?: str_replace('_', ' ', ucfirst($setting->setting_key)) }}</label>
                        <input type="text" name="settings[{{ $setting->setting_key }}]" id="s_{{ $setting->setting_key }}" class="form-control" value="{{ $setting->setting_value }}">
                    @endif
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
