{{--
  Display Settings Form (Compact version for dashboard)
  Adapted from Heratio ahg-display _display-settings-form.blade.php
  Uses PostgreSQL via Eloquent, Bootstrap 5, OpenRiC namespaces.
--}}
@php
    $displayService = app(\OpenRiC\Display\Contracts\DisplayServiceInterface::class);
    $allSettings = $displayService->getAllGlobalSettings();

    $allModes = [
        'tree'     => ['name' => 'Hierarchy', 'icon' => 'bi-diagram-3', 'color' => 'success'],
        'grid'     => ['name' => 'Grid',      'icon' => 'bi-grid-3x3-gap', 'color' => 'primary'],
        'gallery'  => ['name' => 'Gallery',   'icon' => 'bi-images', 'color' => 'info'],
        'list'     => ['name' => 'List',       'icon' => 'bi-list-ul', 'color' => 'secondary'],
        'timeline' => ['name' => 'Timeline',   'icon' => 'bi-clock-history', 'color' => 'warning'],
    ];

    $moduleLabels = [
        'record_resource' => ['label' => 'Record Resources', 'icon' => 'bi-archive'],
        'agent'           => ['label' => 'Agents',           'icon' => 'bi-people'],
        'instantiation'   => ['label' => 'Instantiations',   'icon' => 'bi-file-earmark-image'],
        'place'           => ['label' => 'Places',           'icon' => 'bi-geo-alt'],
        'activity'        => ['label' => 'Activities',       'icon' => 'bi-calendar-event'],
        'rule'            => ['label' => 'Rules',            'icon' => 'bi-shield-check'],
        'search'          => ['label' => 'Search Results',   'icon' => 'bi-search'],
    ];
@endphp

<div class="display-settings-dashboard">
    <!-- Quick Overview -->
    <div class="row g-3 mb-4">
        @foreach($allSettings as $setting)
            @php
                $module = $setting['module'];
                if (!isset($moduleLabels[$module])) continue;
                $moduleInfo = $moduleLabels[$module];
                $modeInfo = $allModes[$setting['display_mode']] ?? $allModes['list'];
                $availableModes = $setting['available_modes'] ?? [];
                $isLocked = !($setting['allow_user_override'] ?? 1);
            @endphp
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 display-module-card {{ $isLocked ? 'border-warning' : '' }}"
                     data-module="{{ $module }}">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="card-title mb-1">
                                    <i class="bi {{ $moduleInfo['icon'] }} me-1"></i>
                                    {{ $moduleInfo['label'] }}
                                </h6>
                                <small class="text-muted">{{ $module }}</small>
                            </div>
                            @if($isLocked)
                                <span class="badge bg-warning text-dark" title="User override disabled">
                                    <i class="bi bi-lock-fill"></i>
                                </span>
                            @endif
                        </div>

                        <!-- Current Mode Display -->
                        <div class="current-mode mb-2">
                            <span class="badge bg-{{ $modeInfo['color'] }} px-3 py-2">
                                <i class="bi {{ $modeInfo['icon'] }} me-1"></i>
                                {{ $modeInfo['name'] }}
                            </span>
                            <span class="text-muted small ms-2">
                                {{ $setting['items_per_page'] ?? 30 }}/page
                            </span>
                        </div>

                        <!-- Quick Mode Switcher -->
                        <div class="btn-group btn-group-sm w-100" role="group">
                            @foreach($allModes as $mode => $info)
                                @php
                                    $isAvailable = in_array($mode, $availableModes);
                                    $isActive = $setting['display_mode'] === $mode;
                                @endphp
                                <button type="button"
                                        class="btn btn-outline-{{ $info['color'] }} quick-mode-btn
                                               {{ $isActive ? 'active' : '' }}
                                               {{ !$isAvailable ? 'disabled opacity-25' : '' }}"
                                        data-module="{{ $module }}"
                                        data-mode="{{ $mode }}"
                                        title="{{ $info['name'] }}{{ !$isAvailable ? ' (disabled)' : '' }}">
                                    <i class="bi {{ $info['icon'] }}"></i>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="card-footer bg-transparent p-2">
                        <button type="button"
                                class="btn btn-link btn-sm text-decoration-none p-0 edit-module-btn"
                                data-module="{{ $module }}"
                                data-bs-toggle="modal"
                                data-bs-target="#editDisplayModal">
                            <i class="bi bi-pencil me-1"></i> Edit Settings
                        </button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Bulk Actions -->
    <div class="d-flex justify-content-between align-items-center border-top pt-3">
        <div class="btn-group">
            <button type="button" class="btn btn-outline-primary btn-sm" id="setAllToList">
                <i class="bi bi-list-ul me-1"></i> All to List
            </button>
            <button type="button" class="btn btn-outline-primary btn-sm" id="setAllToGrid">
                <i class="bi bi-grid-3x3-gap me-1"></i> All to Grid
            </button>
        </div>

        <div>
            <button type="button" class="btn btn-outline-danger btn-sm" id="resetAllDefaults">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset All
            </button>
        </div>
    </div>
</div>

<style>
.display-module-card {
    transition: transform 0.2s, box-shadow 0.2s;
}
.display-module-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.display-module-card.border-warning {
    border-width: 2px;
}
.quick-mode-btn {
    padding: 0.25rem 0.5rem;
}
.settings-section-header {
    padding: 1rem;
    background: var(--bs-light);
    border-radius: 0.375rem;
    cursor: pointer;
    transition: background 0.2s;
}
.settings-section-header:hover {
    background: var(--bs-gray-200);
}
.settings-section-header .collapse-icon {
    transition: transform 0.3s;
}
.settings-section-header[aria-expanded="true"] .collapse-icon {
    transform: rotate(180deg);
}
.settings-section-body {
    padding: 1.5rem;
    border: 1px solid var(--bs-border-color);
    border-top: none;
    border-radius: 0 0 0.375rem 0.375rem;
}
</style>
