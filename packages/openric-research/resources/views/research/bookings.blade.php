@extends('theme::layouts.1col')
@section('title', 'Bookings')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Bookings</h2>
            <a href="{{ route('research.book') }}" class="btn btn-primary">New Booking</a>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        @if(!empty($pendingBookings))
        <h5>Pending Bookings</h5>
        <div class="table-responsive mb-4">
            <table class="table table-striped">
                <thead><tr><th>Date</th><th>Researcher</th><th>Room</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @foreach($pendingBookings as $b)
                    <tr>
                        <td>{{ $b->date ?? $b->booking_date }}</td>
                        <td>{{ $b->researcher_name ?? '' }}</td>
                        <td>{{ $b->room_name ?? '' }}</td>
                        <td><span class="badge bg-warning">Pending</span></td>
                        <td><a href="{{ route('research.viewBooking', $b->id) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <h5>Upcoming Confirmed Bookings</h5>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Date</th><th>Researcher</th><th>Room</th><th></th></tr></thead>
                <tbody>
                @forelse($upcomingBookings as $b)
                    <tr>
                        <td>{{ $b->date ?? $b->booking_date }}</td>
                        <td>{{ $b->researcher_name ?? '' }}</td>
                        <td>{{ $b->room_name ?? '' }}</td>
                        <td><a href="{{ route('research.viewBooking', $b->id) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">No upcoming bookings.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
