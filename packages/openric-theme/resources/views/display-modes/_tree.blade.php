{{-- Tree display mode — adapted from Heratio _tree.blade.php (29 lines) + _tree-node.blade.php (52 lines) --}}
{{-- Renders hierarchical data as an expandable tree --}}

@php
    $module = $module ?? 'records';
@endphp

<div class="display-tree-view" data-display-container data-display-mode="tree" role="tree">
    @forelse($items as $item)
        @include('theme::display-modes._tree-node', ['item' => $item, 'depth' => 0])
    @empty
        <div class="text-center text-muted py-5">
            <i class="bi bi-diagram-3 fs-1 d-block mb-2"></i>
            <p>No results found.</p>
        </div>
    @endforelse
</div>
