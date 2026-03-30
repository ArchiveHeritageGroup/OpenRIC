@extends('theme::layouts.1col')
@section('title', 'Team Workspaces')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Team Workspaces</h2>
            <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#createWs">New Workspace</button>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="collapse mb-4" id="createWs">
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('research.workspaces') }}">
                    @csrf <input type="hidden" name="form_action" value="create">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Description</label><input type="text" name="description" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Visibility</label><select name="visibility" class="form-select"><option value="private">Private</option><option value="members">Members</option><option value="public">Public</option></select></div>
                        <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-success w-100">Create</button></div>
                    </div>
                </form>
            </div></div>
        </div>

        <div class="row g-3">
            @forelse($workspaces as $ws)
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5>{{ $ws->name }}</h5>
                            @if($ws->description ?? null)<p class="text-muted small">{{ Str::limit($ws->description, 100) }}</p>@endif
                            <span class="badge bg-secondary">{{ $ws->visibility ?? 'private' }}</span>
                            <span class="badge bg-info">{{ $ws->member_count ?? 1 }} members</span>
                        </div>
                        <div class="card-footer"><small class="text-muted">{{ $ws->created_at ?? '' }}</small></div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted">No workspaces yet.</p></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
