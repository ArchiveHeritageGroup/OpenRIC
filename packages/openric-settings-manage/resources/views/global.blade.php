@extends('theme::layouts.1col')

@section('title', 'Global Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-globe me-2"></i>Global Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.global') }}">
        @csrf
        <div class="accordion" id="globalSettingsAccordion">

            {{-- Site Information --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSite">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSite" aria-expanded="true">
                        <i class="fas fa-info-circle me-2"></i> Site Information
                    </button>
                </h2>
                <div id="collapseSite" class="accordion-collapse collapse show" data-bs-parent="#globalSettingsAccordion">
                    <div class="accordion-body">
                        <div class="mb-3">
                            <label for="site_title" class="form-label">Site Title</label>
                            <input type="text" name="settings[site_title]" id="site_title" class="form-control" value="{{ $settings['site_title'] ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label for="site_description" class="form-label">Site Description</label>
                            <textarea name="settings[site_description]" id="site_description" class="form-control" rows="3">{{ $settings['site_description'] ?? '' }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label for="administrator_email" class="form-label">Administrator Email</label>
                            <input type="email" name="settings[administrator_email]" id="administrator_email" class="form-control" value="{{ $settings['administrator_email'] ?? '' }}">
                        </div>
                        <div class="mb-3">
                            <label for="site_base_url" class="form-label">Site Base URL</label>
                            <input type="url" name="settings[site_base_url]" id="site_base_url" class="form-control" value="{{ $settings['site_base_url'] ?? '' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Locale & Display --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingLocale">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseLocale">
                        <i class="fas fa-language me-2"></i> Locale &amp; Display
                    </button>
                </h2>
                <div id="collapseLocale" class="accordion-collapse collapse" data-bs-parent="#globalSettingsAccordion">
                    <div class="accordion-body">
                        <div class="mb-3">
                            <label for="default_locale" class="form-label">Default Locale</label>
                            <input type="text" name="settings[default_locale]" id="default_locale" class="form-control" value="{{ $settings['default_locale'] ?? 'en_US' }}">
                        </div>
                        <div class="mb-3">
                            <label for="default_timezone" class="form-label">Default Timezone</label>
                            <input type="text" name="settings[default_timezone]" id="default_timezone" class="form-control" value="{{ $settings['default_timezone'] ?? 'UTC' }}">
                        </div>
                        <div class="mb-3">
                            <label for="date_format" class="form-label">Date Format</label>
                            <input type="text" name="settings[date_format]" id="date_format" class="form-control" value="{{ $settings['date_format'] ?? 'Y-m-d' }}">
                        </div>
                        <div class="mb-3">
                            <label for="time_format" class="form-label">Time Format</label>
                            <input type="text" name="settings[time_format]" id="time_format" class="form-control" value="{{ $settings['time_format'] ?? 'H:i:s' }}">
                        </div>
                        <div class="mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <input type="text" name="settings[currency]" id="currency" class="form-control" value="{{ $settings['currency'] ?? 'USD' }}">
                        </div>
                    </div>
                </div>
            </div>

            {{-- System Defaults --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingSystem">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSystem">
                        <i class="fas fa-cog me-2"></i> System Defaults
                    </button>
                </h2>
                <div id="collapseSystem" class="accordion-collapse collapse" data-bs-parent="#globalSettingsAccordion">
                    <div class="accordion-body">
                        <div class="mb-3">
                            <label for="items_per_page" class="form-label">Items Per Page</label>
                            <input type="number" name="settings[items_per_page]" id="items_per_page" class="form-control" value="{{ $settings['items_per_page'] ?? 25 }}" min="5" max="500">
                        </div>
                        <div class="mb-3">
                            <label for="max_export_records" class="form-label">Max Export Records</label>
                            <input type="number" name="settings[max_export_records]" id="max_export_records" class="form-control" value="{{ $settings['max_export_records'] ?? 10000 }}" min="100">
                        </div>
                        <div class="mb-3">
                            <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                            <input type="number" name="settings[session_timeout]" id="session_timeout" class="form-control" value="{{ $settings['session_timeout'] ?? 30 }}" min="5" max="1440">
                        </div>
                        <div class="mb-3">
                            <label for="cache_ttl" class="form-label">Cache TTL (seconds)</label>
                            <input type="number" name="settings[cache_ttl]" id="cache_ttl" class="form-control" value="{{ $settings['cache_ttl'] ?? 3600 }}" min="0">
                        </div>
                        <div class="form-check mb-3">
                            <input type="hidden" name="settings[enable_logging]" value="0">
                            <input class="form-check-input" type="checkbox" name="settings[enable_logging]" id="enable_logging" value="1" {{ ($settings['enable_logging'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_logging">Enable Application Logging</label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Features --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingFeatures">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFeatures">
                        <i class="fas fa-toggle-on me-2"></i> Feature Toggles
                    </button>
                </h2>
                <div id="collapseFeatures" class="accordion-collapse collapse" data-bs-parent="#globalSettingsAccordion">
                    <div class="accordion-body">
                        <div class="form-check mb-3">
                            <input type="hidden" name="settings[enable_search]" value="0">
                            <input class="form-check-input" type="checkbox" name="settings[enable_search]" id="enable_search" value="1" {{ ($settings['enable_search'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_search">Enable Search</label>
                        </div>
                        <div class="form-check mb-3">
                            <input type="hidden" name="settings[enable_public_access]" value="0">
                            <input class="form-check-input" type="checkbox" name="settings[enable_public_access]" id="enable_public_access" value="1" {{ ($settings['enable_public_access'] ?? '0') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_public_access">Enable Public Access</label>
                        </div>
                        <div class="form-check mb-3">
                            <input type="hidden" name="settings[enable_api]" value="0">
                            <input class="form-check-input" type="checkbox" name="settings[enable_api]" id="enable_api" value="1" {{ ($settings['enable_api'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_api">Enable REST API</label>
                        </div>
                        <div class="form-check mb-3">
                            <input type="hidden" name="settings[enable_graphql]" value="0">
                            <input class="form-check-input" type="checkbox" name="settings[enable_graphql]" id="enable_graphql" value="1" {{ ($settings['enable_graphql'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_graphql">Enable GraphQL API</label>
                        </div>
                        <div class="form-check mb-3">
                            <input type="hidden" name="settings[enable_oai]" value="0">
                            <input class="form-check-input" type="checkbox" name="settings[enable_oai]" id="enable_oai" value="1" {{ ($settings['enable_oai'] ?? '0') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_oai">Enable OAI-PMH</label>
                        </div>
                        <div class="form-check mb-3">
                            <input type="hidden" name="settings[maintenance_mode]" value="0">
                            <input class="form-check-input" type="checkbox" name="settings[maintenance_mode]" id="maintenance_mode" value="1" {{ ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save All Settings</button>
        </div>
    </form>
</div>
@endsection
