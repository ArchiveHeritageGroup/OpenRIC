@extends('theme::layouts.2col')
@section('sidebar')
<div class="sidebar-content">
    <div class="card mb-3"><div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-layer-group me-2"></i>{{ e($collection->display_name) }}</h5></div><div class="card-body"><a href="{{ route('iiif-collection.view', $collection->id) }}" class="btn btn-outline-secondary w-100"><i class="fas fa-arrow-left me-2"></i>Back to Collection</a></div></div>
    <div class="card"><div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-link me-2"></i>Add External Manifest</h5></div>
    <div class="card-body"><form method="POST" action="{{ route('iiif-collection.add-items', $collection->id) }}">@csrf
        <div class="mb-3"><label class="form-label">Manifest URI</label><input type="url" class="form-control form-control-sm" name="manifest_uri" placeholder="https://..."></div>
        <div class="mb-3"><label class="form-label">Label</label><input type="text" class="form-control form-control-sm" name="label"></div>
        <button type="submit" class="btn btn-sm btn-outline-success w-100"><i class="fas fa-plus me-2"></i>Add External</button>
    </form></div></div>
</div>
@endsection
@section('title-block')<h1><i class="fas fa-plus-circle me-2"></i>Add Items to Collection</h1><h2>{{ e($collection->display_name) }}</h2>@endsection
@section('content')
<div class="add-items-form">
    <div class="card mb-4"><div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="fas fa-search me-2"></i>Search & Add Objects</h5></div>
    <div class="card-body">
        <form method="POST" id="addItemsForm" action="{{ route('iiif-collection.add-items', $collection->id) }}">@csrf
            <div class="mb-3"><label class="form-label">Search for objects</label><input type="text" class="form-control" id="objectSearchInput" placeholder="Type to search by title or identifier..." autocomplete="off"><div id="searchResults" class="list-group mt-2" style="max-height:300px;overflow-y:auto;"></div></div>
            <div class="mb-3"><label class="form-label">Selected Items</label><div id="selectedItems" class="border rounded p-2" style="min-height:50px;"><span class="text-muted" id="noSelection">No items selected</span></div></div>
            <button type="submit" class="btn btn-outline-success btn-lg" id="addBtn" disabled><i class="fas fa-plus me-2"></i>Add Selected Items to Collection</button>
        </form>
    </div></div>
</div>
<script>
(function() {
    var searchInput = document.getElementById('objectSearchInput'), searchResults = document.getElementById('searchResults'), selectedItems = document.getElementById('selectedItems'), noSelection = document.getElementById('noSelection'), addBtn = document.getElementById('addBtn'), selected = {}, searchTimeout;
    searchInput.addEventListener('input', function() {
        var query = this.value.trim(); clearTimeout(searchTimeout);
        if (query.length < 2) { searchResults.innerHTML = ''; return; }
        searchTimeout = setTimeout(function() {
            fetch('{{ route('iiif-collection.autocomplete') }}?q=' + encodeURIComponent(query)).then(function(r) { return r.json(); }).then(function(data) {
                searchResults.innerHTML = '';
                if (data.results && data.results.length > 0) { data.results.forEach(function(item) { if (!selected[item.id]) { var div = document.createElement('a'); div.href = '#'; div.className = 'list-group-item list-group-item-action'; div.innerHTML = '<strong>' + (item.title || 'Untitled') + '</strong>' + (item.identifier ? ' <code class="ms-2 small">' + item.identifier + '</code>' : ''); div.onclick = function(e) { e.preventDefault(); addToSelected(item); searchInput.value = ''; searchResults.innerHTML = ''; }; searchResults.appendChild(div); } }); } else { searchResults.innerHTML = '<div class="list-group-item text-muted">No results found</div>'; }
            });
        }, 300);
    });
    function addToSelected(item) { if (selected[item.id]) return; selected[item.id] = item; noSelection.style.display = 'none'; var card = document.createElement('div'); card.className = 'card mb-2'; card.id = 'sel-' + item.id; card.innerHTML = '<div class="card-body p-2"><div class="d-flex justify-content-between align-items-start"><div><strong>' + (item.title || 'Untitled') + '</strong>' + (item.identifier ? ' <code class="ms-2 small">' + item.identifier + '</code>' : '') + '</div><button type="button" class="btn btn-sm btn-outline-danger remove-btn"><i class="fas fa-times"></i></button></div><input type="hidden" name="object_ids[]" value="' + item.id + '"></div>'; card.querySelector('.remove-btn').onclick = function() { delete selected[item.id]; card.remove(); updateUI(); }; selectedItems.appendChild(card); updateUI(); }
    function updateUI() { var count = Object.keys(selected).length; addBtn.disabled = count === 0; noSelection.style.display = count === 0 ? '' : 'none'; }
})();
</script>
@endsection
