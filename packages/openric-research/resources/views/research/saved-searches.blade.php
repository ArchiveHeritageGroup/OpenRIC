@extends('theme::layouts.1col')
@section('title', 'Saved Searches')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Saved Searches</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="card mb-4">
            <div class="card-body">
                <form method="POST" action="{{ route('research.savedSearches') }}" class="row g-3">
                    @csrf <input type="hidden" name="booking_action" value="save">
                    <div class="col-md-4"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required></div>
                    <div class="col-md-5"><label class="form-label">Search Query *</label><input type="text" name="search_query" class="form-control" required></div>
                    <div class="col-md-3 d-flex align-items-end"><button type="submit" class="btn btn-primary w-100">Save Search</button></div>
                </form>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Name</th><th>Query</th><th>Created</th><th></th></tr></thead>
                <tbody>
                @forelse($savedSearches as $s)
                    <tr>
                        <td>{{ $s->name }}</td>
                        <td><code>{{ $s->search_query }}</code></td>
                        <td><small>{{ $s->created_at }}</small></td>
                        <td>
                            <a href="{{ route('research.runSavedSearch', $s->id) }}" class="btn btn-sm btn-outline-primary">Run</a>
                            <form method="POST" action="{{ route('research.savedSearches') }}" class="d-inline">@csrf <input type="hidden" name="booking_action" value="delete"><input type="hidden" name="id" value="{{ $s->id }}"><button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">No saved searches.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
