@extends('theme::layouts.1col')
@section('title', 'Reading Room Seats')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Reading Room Seats</h2>
        <form method="GET" action="{{ route('research.seats') }}" class="mb-3">
            <div class="row g-3"><div class="col-md-4">
                <select name="room_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Select Room --</option>
                    @foreach($rooms as $r)<option value="{{ $r->id }}" {{ $roomId == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>@endforeach
                </select>
            </div></div>
        </form>
        @if($currentRoom)
            <h5>{{ $currentRoom->name }} - Seats</h5>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead><tr><th>Seat Number</th><th>Type</th><th>Status</th><th>Notes</th></tr></thead>
                    <tbody>
                    @forelse($seats as $s)
                        <tr><td>{{ $s->seat_number ?? $s->id }}</td><td>{{ $s->seat_type ?? '-' }}</td><td><span class="badge bg-{{ ($s->is_available ?? true) ? 'success' : 'warning' }}">{{ ($s->is_available ?? true) ? 'Available' : 'Occupied' }}</span></td><td>{{ $s->notes ?? '-' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-muted text-center">No seats configured for this room.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
