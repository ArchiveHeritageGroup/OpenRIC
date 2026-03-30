@extends('theme::layouts.1col')
@section('title', 'Bibliography: ' . ($bibliography->name ?? ''))
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">{{ $bibliography->name }}</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if($bibliography->description)<p class="text-muted">{{ $bibliography->description }}</p>@endif
        <p><span class="badge bg-info">Style: {{ $bibliography->citation_style ?? 'chicago' }}</span></p>

        {{-- Add Entry --}}
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Add Entry from Object</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('research.viewBibliography', $bibliography->id) }}" class="row g-3">
                    @csrf <input type="hidden" name="form_action" value="add_entry">
                    <div class="col-md-4"><input type="number" name="object_id" class="form-control" placeholder="Object ID" required></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-success">Add</button></div>
                </form>
            </div>
        </div>

        {{-- Entries --}}
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Entries ({{ count($entries) }})</h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>#</th><th>Title</th><th>Authors</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                    @forelse($entries as $i => $e)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $e->title ?? 'Untitled' }}</td>
                            <td>{{ $e->authors ?? '-' }}</td>
                            <td>{{ $e->date ?? '-' }}</td>
                            <td>
                                <form method="POST" action="{{ route('research.viewBibliography', $bibliography->id) }}" class="d-inline">
                                    @csrf <input type="hidden" name="form_action" value="remove_entry"><input type="hidden" name="entry_id" value="{{ $e->id }}">
                                    <button class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center">No entries.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            <a href="{{ route('research.bibliographies') }}" class="btn btn-outline-secondary">Back</a>
            <form method="POST" action="{{ route('research.viewBibliography', $bibliography->id) }}" class="d-inline">
                @csrf <input type="hidden" name="form_action" value="delete">
                <button class="btn btn-outline-danger" onclick="return confirm('Delete bibliography?')">Delete Bibliography</button>
            </form>
        </div>
    </div>
</div>
@endsection
