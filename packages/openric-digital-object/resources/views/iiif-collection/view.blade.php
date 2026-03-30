@extends('theme::layouts.2col')

@section('sidebar')
<div class="sidebar-content">
    <div class="card mb-3"><div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-cog me-2"></i>Actions</h5></div>
    <div class="card-body">
        <a href="{{ route('iiif-collection.manifest', $collection->slug) }}" class="btn btn-outline-secondary w-100 mb-2" target="_blank"><i class="fas fa-code me-2"></i>View IIIF JSON</a>
        @auth
        <a href="{{ route('iiif-collection.add-items', $collection->id) }}" class="btn btn-outline-success w-100 mb-2"><i class="fas fa-plus me-2"></i>Add Items</a>
        <a href="{{ route('iiif-collection.edit', $collection->id) }}" class="btn btn-outline-secondary w-100 mb-2"><i class="fas fa-edit me-2"></i>Edit Collection</a>
        <a href="{{ route('iiif-collection.create', ['parent_id' => $collection->id]) }}" class="btn btn-outline-success w-100 mb-2"><i class="fas fa-folder-plus me-2"></i>Create Subcollection</a>
        <hr>
        <form method="POST" action="{{ route('iiif-collection.destroy', $collection->id) }}" onsubmit="return confirm('Are you sure you want to delete this collection?')">@csrf @method('DELETE')
            <button type="submit" class="btn btn-outline-danger w-100"><i class="fas fa-trash me-2"></i>Delete Collection</button>
        </form>
        @endauth
    </div></div>
    <div class="card"><div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Details</h5></div>
    <div class="card-body"><dl class="mb-0">
        <dt>Items</dt><dd>{{ count($collection->items) }}</dd>
        <dt>Subcollections</dt><dd>{{ count($collection->subcollections) }}</dd>
        <dt>Visibility</dt><dd>@if($collection->is_public)<span class="badge bg-success">Public</span>@else<span class="badge bg-warning">Private</span>@endif</dd>
        @if($collection->viewing_hint)<dt>Viewing Hint</dt><dd><code>{{ e($collection->viewing_hint) }}</code></dd>@endif
        <dt>IIIF URI</dt><dd><small><code>{{ route('iiif-collection.manifest', $collection->slug) }}</code></small></dd>
    </dl></div></div>
</div>
@endsection

@section('title-block')
<nav aria-label="breadcrumb"><ol class="breadcrumb mb-2"><li class="breadcrumb-item"><a href="{{ route('iiif-collection.index') }}">Collections</a></li>@foreach($breadcrumbs as $bc)@if($bc->id === $collection->id)<li class="breadcrumb-item active">{{ e($bc->display_name) }}</li>@else<li class="breadcrumb-item"><a href="{{ route('iiif-collection.view', $bc->id) }}">{{ e($bc->display_name) }}</a></li>@endif @endforeach</ol></nav>
<h1><i class="fas fa-layer-group me-2"></i>{{ e($collection->display_name) }}</h1>
@endsection

@section('content')
<div class="iiif-collection-view">
    @if($collection->display_description)<div class="lead mb-4">{{ e($collection->display_description) }}</div>@endif
    @if($collection->attribution)<p class="text-muted"><strong>Attribution:</strong> {{ e($collection->attribution) }}</p>@endif

    @if(!empty($collection->subcollections))
    <div class="card mb-4"><div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-folder me-2"></i>Subcollections</h5></div>
    <div class="card-body"><div class="row row-cols-1 row-cols-md-3 g-3">@foreach($collection->subcollections as $sub)<div class="col"><div class="card h-100"><div class="card-body"><h6 class="card-title"><a href="{{ route('iiif-collection.view', $sub->id) }}"><i class="fas fa-folder me-1"></i>{{ e($sub->display_name) }}</a></h6><span class="badge bg-secondary">{{ $sub->item_count }} items</span></div></div></div>@endforeach</div></div></div>
    @endif

    <div class="card"><div class="card-header d-flex justify-content-between align-items-center bg-primary text-white"><h5 class="mb-0"><i class="fas fa-images me-2"></i>Items ({{ count($collection->items) }})</h5></div>
    <div class="card-body">
        @if(empty($collection->items))
        <div class="alert alert-info mb-0"><i class="fas fa-info-circle me-2"></i>No items in this collection yet. @auth <a href="{{ route('iiif-collection.add-items', $collection->id) }}">Add items</a> @endauth</div>
        @else
        <div class="table-responsive"><table class="table table-bordered table-hover align-middle"><thead><tr><th style="width:50px;"></th><th>Title</th><th>Identifier</th><th>Type</th><th style="width:150px;">Actions</th></tr></thead>
        <tbody class="sortable-items">
            @foreach($collection->items as $item)
            <tr data-item-id="{{ $item->id }}">
                <td class="drag-handle text-center text-muted"><i class="fas fa-grip-vertical"></i></td>
                <td>@if($item->slug)<a href="{{ url('/' . $item->slug) }}">{{ e($item->label ?: $item->object_title ?: 'Untitled') }}</a>@elseif($item->manifest_uri)<a href="{{ e($item->manifest_uri) }}" target="_blank">{{ e($item->label ?: 'External Manifest') }} <i class="fas fa-external-link-alt ms-1 small"></i></a>@else {{ e($item->label ?: 'Untitled') }}@endif</td>
                <td><code>{{ e($item->identifier ?: '-') }}</code></td>
                <td>@if($item->item_type === 'collection')<span class="badge bg-info">Collection</span>@else<span class="badge bg-primary">Manifest</span>@endif</td>
                <td>
                    @if($item->slug)<a href="{{ route('iiif-collection.object-manifest', $item->slug) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="View IIIF Manifest"><i class="fas fa-code"></i></a>@endif
                    @auth <a href="{{ route('iiif-collection.remove-item', ['item_id' => $item->id, 'collection_id' => $collection->id]) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this item from the collection?')" title="Remove"><i class="fas fa-times"></i></a> @endauth
                </td>
            </tr>
            @endforeach
        </tbody></table></div>
        @endif
    </div></div>
</div>
@auth
<script>
document.addEventListener('DOMContentLoaded', function() {
    var tbody = document.querySelector('.sortable-items');
    if (tbody && typeof Sortable !== 'undefined') {
        new Sortable(tbody, { handle: '.drag-handle', animation: 150, onEnd: function(evt) {
            var itemIds = Array.from(tbody.querySelectorAll('tr')).map(function(row) { return row.dataset.itemId; });
            fetch('{{ route('iiif-collection.reorder') }}', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: 'collection_id={{ $collection->id }}&item_ids[]=' + itemIds.join('&item_ids[]=') });
        }});
    }
});
</script>
@endauth
<style>.drag-handle { cursor: grab; } .drag-handle:active { cursor: grabbing; } .sortable-ghost { opacity: 0.4; background: #f0f0f0; }</style>
@endsection
