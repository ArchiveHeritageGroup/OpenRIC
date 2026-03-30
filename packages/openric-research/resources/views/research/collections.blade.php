@extends('theme::layouts.1col')
@section('title', 'Collections')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Collections</h2>
            <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#createForm">New Collection</button>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

        <div class="collapse {{ ($showCreateForm ?? false) ? 'show' : '' }} mb-4" id="createForm">
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('research.collections') }}">
                    @csrf <input type="hidden" name="do" value="create">
                    <div class="row g-3">
                        <div class="col-md-5"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-5"><label class="form-label">Description</label><input type="text" name="description" class="form-control"></div>
                        <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-success w-100">Create</button></div>
                    </div>
                </form>
            </div></div>
        </div>

        <div class="row g-3">
            @forelse($collections as $c)
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><a href="{{ route('research.viewCollection', $c->id) }}">{{ $c->name }}</a></h5>
                            @if($c->description)<p class="card-text text-muted small">{{ Str::limit($c->description, 100) }}</p>@endif
                            <p class="mb-0"><span class="badge bg-info">{{ $c->item_count ?? 0 }} items</span>
                            @if($c->is_public ?? false)<span class="badge bg-success">Public</span>@endif</p>
                        </div>
                        <div class="card-footer"><small class="text-muted">Created {{ $c->created_at }}</small></div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted">No collections yet. Create your first collection above.</p></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
