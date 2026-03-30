@extends('theme::layouts.1col')
@section('title', 'Reading Rooms')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Reading Rooms</h2>
            <a href="{{ route('research.editRoom') }}" class="btn btn-primary">Add Room</a>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row g-3">
            @forelse($rooms as $room)
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5>{{ $room->name }}</h5>
                            @if($room->location)<p class="text-muted small mb-1">{{ $room->location }}</p>@endif
                            <p class="mb-1">Capacity: {{ $room->capacity ?? '-' }}</p>
                            <p class="mb-0">
                                <span class="badge bg-{{ ($room->is_active ?? false) ? 'success' : 'danger' }}">{{ ($room->is_active ?? false) ? 'Active' : 'Inactive' }}</span>
                                @if($room->opening_time && $room->closing_time)<span class="badge bg-info">{{ $room->opening_time }} - {{ $room->closing_time }}</span>@endif
                            </p>
                        </div>
                        <div class="card-footer"><a href="{{ route('research.editRoom', ['id' => $room->id]) }}" class="btn btn-sm btn-outline-primary">Edit</a></div>
                    </div>
                </div>
            @empty
                <div class="col-12"><p class="text-muted">No reading rooms configured.</p></div>
            @endforelse
        </div>
    </div>
</div>
@endsection
