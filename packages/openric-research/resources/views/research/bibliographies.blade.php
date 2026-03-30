@extends('theme::layouts.1col')
@section('title', 'Bibliographies')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Bibliographies</h2>
            <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#createBib">New Bibliography</button>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="collapse mb-4" id="createBib">
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('research.bibliographies') }}">
                    @csrf <input type="hidden" name="form_action" value="create">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">Description</label><input type="text" name="description" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Style</label><select name="citation_style" class="form-select"><option value="chicago">Chicago</option><option value="mla">MLA</option><option value="apa">APA</option><option value="harvard">Harvard</option><option value="turabian">Turabian</option></select></div>
                        <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-success w-100">Create</button></div>
                    </div>
                </form>
            </div></div>
        </div>

        <div class="row g-3">
            @forelse($bibliographies as $b)
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5><a href="{{ route('research.viewBibliography', $b->id) }}">{{ $b->name }}</a></h5>
                            @if($b->description)<p class="text-muted small">{{ $b->description }}</p>@endif
                            <span class="badge bg-info">{{ $b->citation_style ?? 'chicago' }}</span>
                        </div>
                        <div class="card-footer"><small class="text-muted">{{ $b->created_at }}</small></div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted">No bibliographies yet.</p></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
