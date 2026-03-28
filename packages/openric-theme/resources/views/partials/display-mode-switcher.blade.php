{{-- Display mode switcher — controls for switching between list/gallery/grid/timeline/tree views --}}
{{-- Adapted from Heratio display mode pattern --}}

@php
    $currentMode = $displayMode ?? ($themeData['displayMode'] ?? 'list');
    $modes = [
        'list' => ['icon' => 'bi-list-ul', 'label' => 'List'],
        'gallery' => ['icon' => 'bi-images', 'label' => 'Gallery'],
        'grid' => ['icon' => 'bi-grid-3x3-gap', 'label' => 'Grid'],
        'timeline' => ['icon' => 'bi-clock-history', 'label' => 'Timeline'],
        'tree' => ['icon' => 'bi-diagram-3', 'label' => 'Tree'],
    ];
@endphp

<div class="btn-group btn-group-sm" role="group" aria-label="Display mode">
    @foreach($modes as $mode => $config)
        <button type="button"
                class="btn {{ $currentMode === $mode ? 'btn-primary' : 'btn-outline-secondary' }} display-mode-btn"
                data-display-mode="{{ $mode }}"
                title="{{ $config['label'] }} view"
                aria-pressed="{{ $currentMode === $mode ? 'true' : 'false' }}">
            <i class="bi {{ $config['icon'] }}"></i>
            <span class="d-none d-md-inline ms-1">{{ $config['label'] }}</span>
        </button>
    @endforeach
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.display-mode-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var mode = this.dataset.displayMode;

            // Update button states
            document.querySelectorAll('.display-mode-btn').forEach(function(b) {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-secondary');
                b.setAttribute('aria-pressed', 'false');
            });
            this.classList.remove('btn-outline-secondary');
            this.classList.add('btn-primary');
            this.setAttribute('aria-pressed', 'true');

            // Toggle display containers
            document.querySelectorAll('[data-display-container]').forEach(function(container) {
                container.style.display = container.dataset.displayMode === mode ? '' : 'none';
            });

            // Save preference via AJAX if route exists
            fetch(window.location.pathname, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).catch(function() {});

            // Announce to screen readers
            if (typeof openricAnnounce === 'function') {
                openricAnnounce('Switched to ' + mode + ' view');
            }
        });
    });
});
</script>
