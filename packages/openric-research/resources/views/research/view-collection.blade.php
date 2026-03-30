@extends('theme::layouts.1col')
@section('title', 'Collection: ' . ($collection->name ?? ''))
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">{{ $collection->name }}</h2>
            <div><a href="{{ route('research.collections') }}" class="btn btn-outline-secondary">Back</a></div>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        @if($collection->description)<p class="text-muted">{{ $collection->description }}</p>@endif

        {{-- Edit Collection --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Collection Settings</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('research.viewCollection', $collection->id) }}" class="row g-3">
                    @csrf <input type="hidden" name="booking_action" value="update">
                    <div class="col-md-4"><input type="text" name="name" class="form-control" value="{{ $collection->name }}" required></div>
                    <div class="col-md-4"><input type="text" name="description" class="form-control" value="{{ $collection->description }}" placeholder="Description"></div>
                    <div class="col-md-2"><div class="form-check mt-2"><input type="checkbox" name="is_public" class="form-check-input" {{ ($collection->is_public ?? false) ? 'checked' : '' }}><label class="form-check-label">Public</label></div></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Save</button></div>
                </form>
            </div>
        </div>

        {{-- Add Item --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Add Item</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('research.viewCollection', $collection->id) }}" class="row g-3">
                    @csrf <input type="hidden" name="booking_action" value="add_item">
                    <div class="col-md-4"><input type="number" name="object_id" class="form-control" placeholder="Object ID" required></div>
                    <div class="col-md-5"><input type="text" name="notes" class="form-control" placeholder="Notes (optional)"></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-success w-100">Add Item</button></div>
                </form>
            </div>
        </div>

        {{-- Items List --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Items ({{ count($collection->items ?? []) }})</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Object ID</th><th>Notes</th><th>Added</th><th></th></tr></thead>
                    <tbody>
                    @forelse($collection->items ?? [] as $item)
                        <tr>
                            <td>{{ $item->object_id }}</td>
                            <td>{{ $item->notes ?? '-' }}</td>
                            <td><small>{{ $item->created_at }}</small></td>
                            <td>
                                <form method="POST" action="{{ route('research.viewCollection', $collection->id) }}" class="d-inline">
                                    @csrf <input type="hidden" name="booking_action" value="remove"> <input type="hidden" name="object_id" value="{{ $item->object_id }}">
                                    <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove this item?')">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center">No items in this collection.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Delete Collection --}}
        <div class="mt-3">
            <form method="POST" action="{{ route('research.viewCollection', $collection->id) }}" class="d-inline">
                @csrf <input type="hidden" name="booking_action" value="delete">
                <button class="btn btn-outline-danger" onclick="return confirm('Delete this entire collection?')">Delete Collection</button>
            </form>
        </div>
    </div>
</div>
@endsection
