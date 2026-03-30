@extends('theme::layouts.1col')
@section('title', 'Journal Entry')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">{{ $entry->title ?: 'Journal Entry' }}</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <form method="POST" action="{{ route('research.journalEntry', $entry->id) }}">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Title</label><input type="text" name="title" class="form-control" value="{{ $entry->title }}"></div>
                        <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="entry_date" class="form-control" value="{{ $entry->entry_date }}"></div>
                        <div class="col-md-3"><label class="form-label">Project</label><select name="project_id" class="form-select"><option value="">-- None --</option>@foreach($projects ?? [] as $p)<option value="{{ $p->id }}" {{ ($entry->project_id ?? '') == $p->id ? 'selected' : '' }}>{{ $p->title }}</option>@endforeach</select></div>
                        <div class="col-md-2"><label class="form-label">Time (min)</label><input type="number" name="time_spent_minutes" class="form-control" value="{{ $entry->time_spent_minutes }}"></div>
                        <div class="col-12"><label class="form-label">Tags</label><input type="text" name="tags" class="form-control" value="{{ $entry->tags }}"></div>
                        <div class="col-12"><label class="form-label">Content</label><textarea name="content" class="form-control" rows="10">{!! $entry->content !!}</textarea></div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Update Entry</button>
                <a href="{{ route('research.journal') }}" class="btn btn-outline-secondary ms-2">Back</a>
                <button type="submit" name="form_action" value="delete" class="btn btn-outline-danger ms-2" onclick="return confirm('Delete this entry?')">Delete</button>
            </div>
        </form>
    </div>
</div>
@endsection
