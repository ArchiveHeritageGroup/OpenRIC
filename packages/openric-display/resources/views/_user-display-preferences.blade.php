{{--
  User Display Preferences Component — _user-display-preferences.blade.php
  Adapted from Heratio ahg-display _user-display-preferences.blade.php
  Uses PostgreSQL, Bootstrap 5, OpenRiC namespaces.
--}}
@php
    $displayService = app(\OpenRiC\Display\Contracts\DisplayServiceInterface::class);

    $modules = [
        'record_resource' => 'Record Resources',
        'agent'           => 'Agents',
        'instantiation'   => 'Instantiations',
        'place'           => 'Places',
        'activity'        => 'Activities',
        'rule'            => 'Rules',
        'search'          => 'Search Results',
    ];

    $allModes = [
        'tree'     => ['name' => 'Hierarchy', 'icon' => 'bi-diagram-3'],
        'grid'     => ['name' => 'Grid',      'icon' => 'bi-grid-3x3-gap'],
        'gallery'  => ['name' => 'Gallery',   'icon' => 'bi-images'],
        'list'     => ['name' => 'List',       'icon' => 'bi-list-ul'],
        'timeline' => ['name' => 'Timeline',   'icon' => 'bi-clock-history'],
    ];
@endphp

<div class="user-display-preferences">
    <h4 class="mb-3">
        <i class="bi bi-display me-2"></i>
        {{ __('Display Preferences') }}
    </h4>

    <p class="text-muted small mb-4">
        {{ __('Customize how content is displayed when browsing different modules. Your preferences will be remembered across sessions.') }}
    </p>

    <div class="accordion" id="displayPrefsAccordion">
        @foreach($modules as $module => $label)
            @php
                $settings = $displayService->getDisplaySettings($module);
                $canOverride = $settings['allow_user_override'] ?? true;
                $hasCustom = $settings['_source'] === 'user';
                $availableModes = $settings['available_modes'] ?? array_keys($allModes);
            @endphp

            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#pref_{{ $module }}">
                        <span class="me-auto">{{ $label }}</span>

                        @if(!$canOverride)
                            <span class="badge bg-secondary me-2" title="Locked by administrator">
                                <i class="bi bi-lock"></i>
                            </span>
                        @elseif($hasCustom)
                            <span class="badge bg-primary me-2" title="Custom preference">
                                <i class="bi bi-person-check"></i>
                            </span>
                        @else
                            <span class="badge bg-light text-dark me-2" title="Using default">Default</span>
                        @endif

                        <span class="badge bg-info">
                            <i class="bi {{ $allModes[$settings['display_mode']]['icon'] ?? 'bi-list-ul' }}"></i>
                            {{ $allModes[$settings['display_mode']]['name'] ?? 'List' }}
                        </span>
                    </button>
                </h2>

                <div id="pref_{{ $module }}" class="accordion-collapse collapse"
                     data-bs-parent="#displayPrefsAccordion">
                    <div class="accordion-body">
                        @if(!$canOverride)
                            <div class="alert alert-secondary">
                                <i class="bi bi-lock me-2"></i>
                                Display mode for this module is set by the administrator.
                            </div>
                        @else
                            <form class="user-pref-form" data-module="{{ $module }}">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">{{ __('Display Mode') }}</label>
                                        <div class="btn-group d-flex" role="group">
                                            @foreach($allModes as $mode => $meta)
                                                @if(in_array($mode, $availableModes))
                                                <input type="radio" class="btn-check"
                                                       name="display_mode"
                                                       id="dm_{{ $module }}_{{ $mode }}"
                                                       value="{{ $mode }}"
                                                       {{ $settings['display_mode'] === $mode ? 'checked' : '' }}>
                                                <label class="btn btn-outline-primary"
                                                       for="dm_{{ $module }}_{{ $mode }}"
                                                       title="{{ $meta['name'] }}">
                                                    <i class="bi {{ $meta['icon'] }}"></i>
                                                    <span class="d-none d-lg-inline ms-1">{{ $meta['name'] }}</span>
                                                </label>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('Items Per Page') }}</label>
                                        <select name="items_per_page" class="form-select">
                                            @foreach([10, 20, 30, 50, 100] as $count)
                                                <option value="{{ $count }}"
                                                    {{ ($settings['items_per_page'] ?? 30) == $count ? 'selected' : '' }}>
                                                    {{ $count }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label class="form-label">{{ __('Card Size') }}</label>
                                        <select name="card_size" class="form-select">
                                            <option value="small" {{ ($settings['card_size'] ?? 'medium') === 'small' ? 'selected' : '' }}>Small</option>
                                            <option value="medium" {{ ($settings['card_size'] ?? 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                                            <option value="large" {{ ($settings['card_size'] ?? 'medium') === 'large' ? 'selected' : '' }}>Large</option>
                                        </select>
                                    </div>

                                    <div class="col-12">
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input"
                                                   name="show_thumbnails" value="1"
                                                   id="thumb_{{ $module }}"
                                                   {{ ($settings['show_thumbnails'] ?? 1) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="thumb_{{ $module }}">Show thumbnails</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input type="checkbox" class="form-check-input"
                                                   name="show_descriptions" value="1"
                                                   id="desc_{{ $module }}"
                                                   {{ ($settings['show_descriptions'] ?? 1) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="desc_{{ $module }}">Show descriptions</label>
                                        </div>
                                    </div>

                                    <div class="col-12 d-flex justify-content-between">
                                        @if($hasCustom)
                                            <button type="button" class="btn btn-outline-secondary btn-sm reset-pref-btn">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset to Default
                                            </button>
                                        @else
                                            <span></span>
                                        @endif
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-save me-1"></i> Save Preference
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const API_BASE = '{{ route("display.save.settings") }}';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    document.querySelectorAll('.user-pref-form').forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            const module = this.dataset.module;
            const data = new FormData(this);
            data.append('action', 'preferences');
            data.append('module', module);
            data.append('_token', csrfToken);

            const submitBtn = this.querySelector('[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Saving...';

            try {
                const response = await fetch(API_BASE, { method: 'POST', body: data });
                const result = await response.json();
                if (result.success) {
                    submitBtn.innerHTML = '<i class="bi bi-check-lg"></i> Saved!';
                    setTimeout(() => { submitBtn.innerHTML = originalText; submitBtn.disabled = false; }, 2000);
                } else { throw new Error(result.error || 'Save failed'); }
            } catch (error) {
                alert('Error: ' + error.message);
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    });

    document.querySelectorAll('.reset-pref-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const form = this.closest('form');
            const module = form.dataset.module;
            if (!confirm('Reset display preferences for this module to default?')) return;
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
});
</script>
@endpush
