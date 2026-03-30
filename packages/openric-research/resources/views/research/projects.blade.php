@extends('theme::layouts.1col')
@section('title', 'Research Projects')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Research Projects</h2>
            <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#createProject">New Project</button>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="collapse {{ ($showCreateForm ?? false) ? 'show' : '' }} mb-4" id="createProject">
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('research.projects') }}">
                    @csrf <input type="hidden" name="form_action" value="create">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Title *</label><input type="text" name="title" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Type</label><select name="project_type" class="form-select"><option value="personal">Personal</option><option value="collaborative">Collaborative</option><option value="institutional">Institutional</option><option value="thesis">Thesis</option></select></div>
                        <div class="col-md-3"><label class="form-label">Institution</label><input type="text" name="institution" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Start Date</label><input type="date" name="start_date" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">End Date</label><input type="date" name="expected_end_date" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"></textarea></div>
                        <div class="col-12"><button type="submit" class="btn btn-success">Create Project</button></div>
                    </div>
                </form>
            </div></div>
        </div>

        <div class="d-flex gap-2 mb-3">
            @foreach(['' => 'All', 'planning' => 'Planning', 'active' => 'Active', 'completed' => 'Completed', 'archived' => 'Archived'] as $k => $v)
                <a href="{{ route('research.projects', ['status' => $k]) }}" class="btn btn-sm {{ ($status ?? '') === $k ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $v }}</a>
            @endforeach
        </div>

        <div class="row g-3">
            @forelse($projects as $p)
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5><a href="{{ route('research.viewProject', $p->id) }}">{{ $p->title }}</a></h5>
                            @if($p->description)<p class="text-muted small">{{ Str::limit($p->description, 120) }}</p>@endif
                            <span class="badge bg-{{ ($p->status ?? '') === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($p->status ?? 'planning') }}</span>
                            <span class="badge bg-outline-secondary">{{ ucfirst($p->project_type ?? 'personal') }}</span>
                        </div>
                        <div class="card-footer"><small class="text-muted">Created {{ $p->created_at }}</small></div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted">No projects yet.</p></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
