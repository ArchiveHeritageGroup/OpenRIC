{{-- Tree node — recursive component --}}
{{-- Adapted from Heratio _tree-node.blade.php (52 lines) --}}

@php
    $hasChildren = !empty($item['children']) && count($item['children']) > 0;
    $childCount = $item['child_count'] ?? ($hasChildren ? count($item['children']) : 0);
    $depth = $depth ?? 0;

    // Icon mapping by level — from Heratio tree-node pattern
    $levelIcons = [
        'fonds' => 'bi-bank',
        'collection' => 'bi-collection',
        'series' => 'bi-folder2',
        'sub-series' => 'bi-folder',
        'file' => 'bi-file-earmark-text',
        'item' => 'bi-file-earmark',
        'record_set' => 'bi-collection',
        'record' => 'bi-file-earmark-text',
        'record_part' => 'bi-files',
    ];
    $level = strtolower($item['level_of_description'] ?? $item['entity_type'] ?? 'file');
    $icon = $levelIcons[$level] ?? 'bi-file-earmark';
@endphp

<div class="tree-node" role="treeitem" aria-expanded="{{ $hasChildren ? 'true' : 'false' }}"
     style="padding-left: {{ $depth * 1.5 }}rem;">
    <div class="d-flex align-items-center py-1">
        {{-- Expand/collapse toggle --}}
        @if($hasChildren || $childCount > 0)
            <button type="button"
                    class="btn btn-sm btn-link p-0 me-1 tree-toggle"
                    aria-label="Toggle {{ $item['title'] ?? 'node' }}"
                    onclick="this.closest('.tree-node').classList.toggle('collapsed'); this.setAttribute('aria-expanded', this.closest('.tree-node').classList.contains('collapsed') ? 'false' : 'true');">
                <i class="bi bi-chevron-down tree-chevron"></i>
            </button>
        @else
            <span class="me-1" style="width: 24px; display: inline-block;"></span>
        @endif

        {{-- Level icon --}}
        <i class="bi {{ $icon }} me-1 text-muted"></i>

        {{-- Title link --}}
        <a href="{{ $item['url'] ?? '#' }}" class="text-decoration-none me-2">
            {{ $item['title'] ?? 'Untitled' }}
        </a>

        {{-- Child count badge --}}
        @if($childCount > 0)
            <span class="badge bg-secondary rounded-pill small">{{ $childCount }}</span>
        @endif

        {{-- Dates --}}
        @if(!empty($item['dates']))
            <span class="text-muted small ms-2">{{ $item['dates'] }}</span>
        @endif
    </div>

    {{-- Child nodes (recursive) --}}
    @if($hasChildren)
        <div class="tree-children">
            @foreach($item['children'] as $child)
                @include('theme::display-modes._tree-node', ['item' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>

@once
<style>
    .tree-node.collapsed > .tree-children { display: none; }
    .tree-node.collapsed .tree-chevron { transform: rotate(-90deg); }
    .tree-chevron { transition: transform 0.15s ease; }
    .tree-toggle:hover { text-decoration: none; }
</style>
@endonce
