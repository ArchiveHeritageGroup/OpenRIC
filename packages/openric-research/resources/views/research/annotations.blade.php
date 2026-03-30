@extends('theme::layouts.1col')
@section('title', 'Research Notes')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Research Notes</h2>
            <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#createNote">New Note</button>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        {{-- Create Note --}}
        <div class="collapse mb-4" id="createNote">
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('research.annotations') }}">
                    @csrf <input type="hidden" name="do" value="create">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Title</label><input type="text" name="title" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Entity Type</label><select name="entity_type" class="form-select"><option value="information_object">Information Object</option><option value="actor">Actor</option><option value="repository">Repository</option><option value="accession">Accession</option><option value="term">Term</option></select></div>
                        <div class="col-md-3"><label class="form-label">Visibility</label><select name="visibility" class="form-select"><option value="private">Private</option><option value="shared">Shared</option><option value="public">Public</option></select></div>
                        <div class="col-md-4"><label class="form-label">Object ID</label><input type="number" name="object_id" class="form-control"></div>
                        <div class="col-md-4"><label class="form-label">Collection</label><select name="collection_id" class="form-select"><option value="">-- None --</option>@foreach($researchCollections ?? [] as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Tags</label><input type="text" name="tags" class="form-control" placeholder="tag1, tag2"></div>
                        <div class="col-12"><label class="form-label">Content *</label><textarea name="content" class="form-control" rows="4" required></textarea></div>
                        <div class="col-12"><button type="submit" class="btn btn-success">Save Note</button></div>
                    </div>
                </form>
            </div></div>
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route('research.annotations') }}" class="mb-3">
            <div class="input-group" style="max-width:400px;">
                <input type="text" name="q" class="form-control" placeholder="Search notes..." value="{{ request('q') }}">
                <button class="btn btn-outline-primary">Search</button>
            </div>
        </form>

        {{-- Notes List --}}
        @forelse($annotations as $ann)
            <div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h6>{{ $ann->title ?: 'Untitled Note' }}</h6>
                        <div>
                            @if($ann->visibility ?? 'private' !== 'private')<span class="badge bg-info">{{ ucfirst($ann->visibility ?? '') }}</span>@endif
                            <small class="text-muted ms-2">{{ $ann->created_at }}</small>
                        </div>
                    </div>
                    <p class="mb-1">{!! Str::limit(strip_tags($ann->content ?? ''), 200) !!}</p>
                    @if($ann->tags)<p class="mb-0"><small class="text-muted">Tags: {{ $ann->tags }}</small></p>@endif
                    <div class="mt-2">
                        <form method="POST" action="{{ route('research.annotations') }}" class="d-inline">@csrf <input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="{{ $ann->id }}"><button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete?')">Delete</button></form>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted">No notes yet. Create your first note above.</p>
        @endforelse
    </div>
</div>
@endsection
