{{--
  Admin Display Mode Settings — display-settings.blade.php
  Adapted from Heratio ahg-display display-settings.blade.php
  Uses PostgreSQL via Eloquent, Bootstrap 5, OpenRiC namespaces.
--}}
@extends('theme::layouts.1col')

@section('title', 'Display Mode Settings')
@section('body-class', 'admin display-settings')

@section('content')
@php
    $displayService = app(\OpenRiC\Display\Contracts\DisplayServiceInterface::class);
    $allSettings = $displayService->getAllGlobalSettings();

    $allModes = [
        'tree'     => ['name' => 'Hierarchy', 'icon' => 'bi-diagram-3'],
        'grid'     => ['name' => 'Grid',      'icon' => 'bi-grid-3x3-gap'],
        'gallery'  => ['name' => 'Gallery',   'icon' => 'bi-images'],
        'list'     => ['name' => 'List',       'icon' => 'bi-list-ul'],
        'timeline' => ['name' => 'Timeline',   'icon' => 'bi-clock-history'],
    ];

    $moduleLabels = [
        'record_resource' => 'Record Resources',
        'agent'           => 'Agents',
        'instantiation'   => 'Instantiations',
        'place'           => 'Places',
        'activity'        => 'Activities',
        'rule'            => 'Rules',
        'search'          => 'Search Results',
    ];
@endphp

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="bi bi-display me-2"></i>
            {{ __('Display Mode Settings') }}
        </h5>
        <span class="badge bg-info">Global Defaults</span>
    </div>

    <div class="card-body">
        <p class="text-muted mb-4">
            Configure default display modes for each module. Users can override these settings
            unless "Lock user override" is enabled.
        </p>

        <form id="globalDisplaySettingsForm" method="post" action="{{ route('display.save.settings') }}">
            @csrf
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 20%;">{{ __('Module') }}</th>
                            <th style="width: 20%;">{{ __('Default Mode') }}</th>
                            <th style="width: 20%;">{{ __('Available Modes') }}</th>
                            <th style="width: 10%;">{{ __('Per Page') }}</th>
                            <th style="width: 15%;">{{ __('Options') }}</th>
                            <th style="width: 15%;">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($allSettings as $setting)
                            @php
                                $module = $setting['module'];
                                $availableModes = $setting['available_modes'] ?? [];
                            @endphp
                            <tr data-module="{{ $module }}">
                                <td>
                                    <strong>{{ $moduleLabels[$module] ?? ucfirst($module) }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $module }}</small>
                                </td>

                                <td>
                                    <select name="settings[{{ $module }}][display_mode]"
                                            class="form-select form-select-sm default-mode-select"
                                            data-module="{{ $module }}">
                                        @foreach($allModes as $mode => $meta)
                                            @if(in_array($mode, $availableModes))
                                                <option value="{{ $mode }}"
                                                    {{ $setting['display_mode'] === $mode ? 'selected' : '' }}>
                                                    {{ $meta['name'] }}
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <div class="available-modes-checkboxes">
                                        @foreach($allModes as $mode => $meta)
                                            <div class="form-check form-check-inline">
                                                <input type="checkbox"
                                                       class="form-check-input available-mode-check"
                                                       name="settings[{{ $module }}][available_modes][]"
                                                       value="{{ $mode }}"
                                                       id="mode_{{ $module }}_{{ $mode }}"
                                                       data-module="{{ $module }}"
                                                       {{ in_array($mode, $availableModes) ? 'checked' : '' }}>
                                                <label class="form-check-label"
                                                       for="mode_{{ $module }}_{{ $mode }}"
                                                       title="{{ $meta['name'] }}">
                                                    <i class="bi {{ $meta['icon'] }}"></i>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>

                                <td>
                                    <select name="settings[{{ $module }}][items_per_page]"
                                            class="form-select form-select-sm">
                                        @foreach([10, 20, 30, 50, 100] as $count)
                                            <option value="{{ $count }}"
                                                {{ ($setting['items_per_page'] ?? 30) == $count ? 'selected' : '' }}>
                                                {{ $count }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <div class="form-check form-switch mb-1">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               name="settings[{{ $module }}][show_thumbnails]"
                                               value="1"
                                               id="thumb_{{ $module }}"
                                               {{ ($setting['show_thumbnails'] ?? 1) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="thumb_{{ $module }}">
                                            Thumbnails
                                        </label>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input type="checkbox"
                                               class="form-check-input"
                                               name="settings[{{ $module }}][allow_user_override]"
                                               value="1"
                                               id="override_{{ $module }}"
                                               {{ ($setting['allow_user_override'] ?? 1) ? 'checked' : '' }}>
                                        <label class="form-check-label small" for="override_{{ $module }}">
                                            Allow user override
                                        </label>
                                    </div>
                                </td>

                                <td>
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm save-module-btn"
                                            data-module="{{ $module }}">
                                        <i class="bi bi-check"></i> Save
                                    </button>
                                    <button type="button"
                                            class="btn btn-outline-warning btn-sm reset-module-btn"
                                            data-module="{{ $module }}"
                                            title="Reset to defaults">
                                        <i class="bi bi-arrow-counterclockwise"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between mt-4">
                <button type="button" class="btn btn-outline-danger" id="resetAllBtn">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>
                    Reset All to Defaults
                </button>

                <button type="submit" class="btn btn-primary" id="saveAllBtn">
                    <i class="bi bi-save me-1"></i>
                    Save All Settings
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Audit Log Section -->
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-clock-history me-2"></i>
            {{ __('Recent Changes') }}
        </h5>
    </div>
    <div class="card-body">
        <div id="auditLogContainer">
            <p class="text-muted">Loading audit log...</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_BASE = '{{ route("display.save.settings") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Save individual module
    document.querySelectorAll('.save-module-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const module = this.dataset.module;
            const row = document.querySelector(`tr[data-module="${module}"]`);

            const data = new FormData();
            data.append('_token', csrfToken);
            data.append('action', 'update');
            data.append('module', module);

            row.querySelectorAll('select, input').forEach(input => {
                if (input.type === 'checkbox') {
                    if (input.name.includes('available_modes')) {
                        if (input.checked) data.append(input.name, input.value);
                    } else {
                        data.append(input.name, input.checked ? 1 : 0);
                    }
                } else {
                    data.append(input.name, input.value);
                }
            });

            this.disabled = true;
            this.innerHTML = '<i class="bi bi-hourglass-split"></i>';

            try {
                const response = await fetch(API_BASE, { method: 'POST', body: data });
                const result = await response.json();
                if (result.success) {
                    this.innerHTML = '<i class="bi bi-check-lg text-success"></i>';
                    setTimeout(() => { this.innerHTML = '<i class="bi bi-check"></i> Save'; this.disabled = false; }, 1500);
                } else {
                    throw new Error(result.error || 'Save failed');
                }
            } catch (error) {
                alert('Error: ' + error.message);
                this.innerHTML = '<i class="bi bi-check"></i> Save';
                this.disabled = false;
            }
        });
    });

    // Reset individual module
    document.querySelectorAll('.reset-module-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const module = this.dataset.module;
            if (!confirm(`Reset ${module} display settings to defaults?`)) return;
            const data = new FormData();
            data.append('_token', csrfToken);
            data.append('action', 'reset');
            data.append('module', module);
            try {
                const response = await fetch(API_BASE, { method: 'POST', body: data });
                const result = await response.json();
                if (result.success) location.reload();
                else throw new Error(result.error || 'Reset failed');
            } catch (error) { alert('Error: ' + error.message); }
        });
    });

    // Update default mode dropdown when available modes change
    document.querySelectorAll('.available-mode-check').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const module = this.dataset.module;
            const modeSelect = document.querySelector(`.default-mode-select[data-module="${module}"]`);
            const checkedModes = [];
            document.querySelectorAll(`.available-mode-check[data-module="${module}"]:checked`).forEach(cb => {
                checkedModes.push(cb.value);
            });
            Array.from(modeSelect.options).forEach(option => { option.disabled = !checkedModes.includes(option.value); });
            if (modeSelect.selectedOptions[0]?.disabled) {
                const firstEnabled = Array.from(modeSelect.options).find(o => !o.disabled);
                if (firstEnabled) modeSelect.value = firstEnabled.value;
            }
        });
    });
});
</script>
@endpush
@endsection
