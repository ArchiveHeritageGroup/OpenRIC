@extends('theme::layouts.1col')

@section('title', $sectionLabel . ' Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        @php
        // Translatable section heading
        $sectionLabelKey = 'settings.sections.' . $section;
        $sectionLabelTranslated = __($sectionLabelKey);
        if ($sectionLabelTranslated === $sectionLabelKey) {
            $sectionLabelTranslated = $sectionLabel;
        }
        @endphp
        <h1 class="h3 mb-0"><i class="fas fa-sliders-h me-2"></i>{{ $sectionLabelTranslated }}</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> {{ __('settings.back_to_settings') }}</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.section', $section) }}">
        @csrf
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0"><i class="fas fa-cog me-2"></i>Configuration</h5>
            </div>
            <div class="card-body">
                @if($settings->isEmpty())
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-1"></i> No settings found in this section.
                    </div>
                @else
                    @foreach($settings as $setting)
                    <div class="row mb-4 pb-3 border-bottom">
                        <div class="col-md-4">
                            @php
                            // Translatable label - use language file key or fallback to key/name
                            $labelKey = 'settings.' . $section . '.' . ($setting->key ?? '');
                            $label = __($labelKey);
                            if ($label === $labelKey) {
                                // Key not found in language file, use the key itself as fallback
                                $label = $setting->key ?? $setting->name ?? 'Setting';
                            }
                            // Description from language file or database
                            $descKey = 'settings.' . $section . '.' . ($setting->key ?? '') . '.description';
                            $description = __($descKey);
                            if ($description === $descKey && !empty($setting->description)) {
                                $description = $setting->description;
                            } elseif ($description === $descKey) {
                                $description = null;
                            }
                            @endphp
                            <label for="setting_{{ $setting->id }}" class="form-label fw-semibold">
                                {{ $label }}
                            </label>
                            @if($description)
                                <p class="text-muted small mb-0">{{ $description }}</p>
                            @endif
                        </div>
                        <div class="col-md-8">
                            @php
                            $value = $setting->value ?? '';
                            $type = $setting->type ?? 'text';
                            $options = $setting->options ?? null;
                            @endphp

                            @if($type === 'boolean' || $type === 'checkbox')
                                <div class="form-check form-switch">
                                    <input type="checkbox" name="settings[{{ $setting->id }}]" id="setting_{{ $setting->id }}"
                                           class="form-check-input" value="1" {{ $value == '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="setting_{{ $setting->id }}">
                                        {{ $value == '1' ? 'Enabled' : 'Disabled' }}
                                    </label>
                                </div>
                            @elseif($type === 'textarea' || str_contains($setting->key ?? '', 'description') || str_contains($setting->key ?? '', 'message'))
                                <textarea name="settings[{{ $setting->id }}]" id="setting_{{ $setting->id }}"
                                          class="form-control" rows="4">{{ $value }}</textarea>
                            @elseif($type === 'select' || is_array($options))
                                <select name="settings[{{ $setting->id }}]" id="setting_{{ $setting->id }}" class="form-select">
                                    @if(is_string($options))
                                        @foreach(explode(',', $options) as $opt)
                                            @php $opt = trim($opt); @endphp
                                            <option value="{{ $opt }}" {{ $value == $opt ? 'selected' : '' }}>{{ $opt }}</option>
                                        @endforeach
                                    @else
                                        <option value="">-- Select --</option>
                                        @foreach($options ?? [] as $optKey => $optLabel)
                                            <option value="{{ $optKey }}" {{ $value == $optKey ? 'selected' : '' }}>{{ $optLabel }}</option>
                                        @endforeach
                                    @endif
                                </select>
                            @elseif($type === 'number' || is_numeric($value))
                                <input type="number" name="settings[{{ $setting->id }}]" id="setting_{{ $setting->id }}"
                                       class="form-control" value="{{ $value }}">
                            @else
                                <input type="text" name="settings[{{ $setting->id }}]" id="setting_{{ $setting->id }}"
                                       class="form-control" value="{{ $value }}">
                            @endif
                        </div>
                    </div>
                    @endforeach
                @endif
            </div>
            @if(!$settings->isEmpty())
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">
                        <i class="fas fa-database me-1"></i> {{ $settings->count() }} setting{{ $settings->count() == 1 ? '' : 's' }} in this section
                    </span>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                </div>
            </div>
            @endif
        </div>
    </form>
</div>
@endsection