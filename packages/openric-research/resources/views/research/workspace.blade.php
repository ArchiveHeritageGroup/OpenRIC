@extends('theme::layouts.1col')
@section('title', 'My Workspace')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">My Workspace</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row g-3 mb-4">
            <div class="col-md-2"><div class="card text-center p-3"><h4>{{ $stats['total_bookings'] ?? 0 }}</h4><small>Bookings</small></div></div>
            <div class="col-md-2"><div class="card text-center p-3"><h4>{{ $stats['total_collections'] ?? 0 }}</h4><small>Collections</small></div></div>
            <div class="col-md-2"><div class="card text-center p-3"><h4>{{ $stats['total_saved_searches'] ?? 0 }}</h4><small>Searches</small></div></div>
            <div class="col-md-2"><div class="card text-center p-3"><h4>{{ $stats['total_annotations'] ?? 0 }}</h4><small>Notes</small></div></div>
            <div class="col-md-2"><div class="card text-center p-3"><h4>{{ $stats['total_items'] ?? 0 }}</h4><small>Items</small></div></div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between"><h5 class="mb-0">Collections</h5><a href="{{ route('research.collections') }}" class="btn btn-sm btn-outline-primary">View All</a></div>
                    <ul class="list-group list-group-flush">
                        @forelse(array_slice($collections, 0, 5) as $c)
                            <li class="list-group-item"><a href="{{ route('research.viewCollection', $c->id) }}">{{ $c->name }}</a> <small class="text-muted">({{ $c->item_count ?? 0 }} items)</small></li>
                        @empty
                            <li class="list-group-item text-muted">No collections yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between"><h5 class="mb-0">Upcoming Bookings</h5><a href="{{ route('research.book') }}" class="btn btn-sm btn-outline-primary">Book</a></div>
                    <ul class="list-group list-group-flush">
                        @forelse($upcomingBookings as $b)
                            <li class="list-group-item">{{ $b->booking_date }} - {{ $b->room_name ?? '' }} <span class="badge bg-{{ $b->status === 'confirmed' ? 'success' : 'warning' }}">{{ $b->status }}</span></li>
                        @empty
                            <li class="list-group-item text-muted">No upcoming bookings.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Saved Searches</h5></div>
                    <ul class="list-group list-group-flush">
                        @forelse(array_slice($savedSearches, 0, 5) as $s)
                            <li class="list-group-item">{{ $s->name }} <small class="text-muted">{{ $s->search_query }}</small></li>
                        @empty
                            <li class="list-group-item text-muted">No saved searches.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Recent Notes</h5></div>
                    <ul class="list-group list-group-flush">
                        @forelse(array_slice($annotations, 0, 5) as $a)
                            <li class="list-group-item">{{ $a->title ?: Str::limit($a->content ?? '', 50) }} <small class="text-muted d-block">{{ $a->created_at }}</small></li>
                        @empty
                            <li class="list-group-item text-muted">No notes yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Quick Create Collection --}}
        <div class="card mt-4">
            <div class="card-header"><h5 class="mb-0">Quick Create Collection</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('research.workspace') }}" class="row g-3">
                    @csrf
                    <input type="hidden" name="booking_action" value="create_collection">
                    <div class="col-md-4"><input type="text" name="collection_name" class="form-control" placeholder="Collection name" required></div>
                    <div class="col-md-5"><input type="text" name="collection_description" class="form-control" placeholder="Description (optional)"></div>
                    <div class="col-md-3"><button type="submit" class="btn btn-primary w-100">Create</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
