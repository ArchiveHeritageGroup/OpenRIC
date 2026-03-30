@extends('theme::layouts.1col')
@section('title', 'Research Journal')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Research Journal</h2>
            <button class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#newEntry">New Entry</button>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        {{-- New Entry Form --}}
        <div class="collapse {{ ($showCreateForm ?? false) ? 'show' : '' }} mb-4" id="newEntry">
            <div class="card"><div class="card-body">
                <form method="POST" action="{{ route('research.journal') }}">
                    @csrf <input type="hidden" name="do" value="create">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Title</label><input type="text" name="title" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="entry_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
                        <div class="col-md-3"><label class="form-label">Project</label><select name="project_id" class="form-select"><option value="">-- None --</option>@foreach($projects ?? [] as $p)<option value="{{ $p->id }}">{{ $p->title }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">Time (min)</label><input type="number" name="time_spent_minutes" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Type</label><select name="entry_type" class="form-select"><option value="manual">Manual</option><option value="research">Research</option><option value="visit">Visit</option><option value="finding">Finding</option></select></div>
                        <div class="col-md-9"><label class="form-label">Tags</label><input type="text" name="tags" class="form-control" placeholder="tag1, tag2"></div>
                        <div class="col-12"><label class="form-label">Content *</label><textarea name="content" class="form-control" rows="6" required></textarea></div>
                        <div class="col-12"><button type="submit" class="btn btn-success">Save Entry</button></div>
                    </div>
                </form>
            </div></div>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('research.journal') }}" class="row g-2 mb-3">
            <div class="col-auto"><input type="text" name="q" class="form-control form-control-sm" placeholder="Search..." value="{{ $filters['search'] ?? '' }}"></div>
            <div class="col-auto"><select name="project_id" class="form-select form-select-sm"><option value="">All Projects</option>@foreach($projects ?? [] as $p)<option value="{{ $p->id }}" {{ ($filters['project_id'] ?? '') == $p->id ? 'selected' : '' }}>{{ $p->title }}</option>@endforeach</select></div>
            <div class="col-auto"><input type="date" name="date_from" class="form-control form-control-sm" value="{{ $filters['date_from'] ?? '' }}"></div>
            <div class="col-auto"><input type="date" name="date_to" class="form-control form-control-sm" value="{{ $filters['date_to'] ?? '' }}"></div>
            <div class="col-auto"><button class="btn btn-sm btn-outline-primary">Filter</button></div>
        </form>

        {{-- Entries --}}
        @forelse($entries as $entry)
            <div class="card mb-2">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h6><a href="{{ route('research.journalEntry', $entry->id) }}">{{ $entry->title ?: 'Untitled Entry' }}</a></h6>
                        <small class="text-muted">{{ $entry->entry_date }}</small>
                    </div>
                    <p class="mb-1 text-muted">{!! Str::limit(strip_tags($entry->content), 200) !!}</p>
                    @if($entry->tags)<small class="text-muted">Tags: {{ $entry->tags }}</small>@endif
                    @if($entry->time_spent_minutes)<span class="badge bg-secondary ms-2">{{ $entry->time_spent_minutes }} min</span>@endif
                </div>
            </div>
        @empty
            <p class="text-muted">No journal entries yet.</p>
        @endforelse
    </div>
</div>
@endsection
