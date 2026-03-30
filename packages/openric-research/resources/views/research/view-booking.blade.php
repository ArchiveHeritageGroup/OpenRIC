@extends('theme::layouts.1col')
@section('title', 'View Booking')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Booking Details</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="row g-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Booking #{{ $booking->id }}</h5>
                        <span class="badge bg-{{ $booking->status === 'confirmed' ? 'success' : ($booking->status === 'pending' ? 'warning' : ($booking->status === 'cancelled' ? 'danger' : 'secondary')) }} fs-6">{{ ucfirst($booking->status) }}</span>
                    </div>
                    <div class="card-body">
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Researcher</dt><dd class="col-sm-8">{{ $booking->first_name }} {{ $booking->last_name }} ({{ $booking->email }})</dd>
                            <dt class="col-sm-4">Institution</dt><dd class="col-sm-8">{{ $booking->institution ?? '-' }}</dd>
                            <dt class="col-sm-4">Room</dt><dd class="col-sm-8">{{ $booking->room_name ?? '' }} {{ $booking->room_location ? '('.$booking->room_location.')' : '' }}</dd>
                            <dt class="col-sm-4">Date</dt><dd class="col-sm-8">{{ $booking->booking_date }}</dd>
                            <dt class="col-sm-4">Time</dt><dd class="col-sm-8">{{ $booking->start_time ?? '' }} - {{ $booking->end_time ?? '' }}</dd>
                            <dt class="col-sm-4">Purpose</dt><dd class="col-sm-8">{{ $booking->purpose ?? '-' }}</dd>
                            <dt class="col-sm-4">Notes</dt><dd class="col-sm-8">{{ $booking->notes ?? '-' }}</dd>
                            @if($booking->checked_in_at)<dt class="col-sm-4">Checked In</dt><dd class="col-sm-8">{{ $booking->checked_in_at }}</dd>@endif
                            @if($booking->checked_out_at)<dt class="col-sm-4">Checked Out</dt><dd class="col-sm-8">{{ $booking->checked_out_at }}</dd>@endif
                        </dl>
                    </div>
                </div>

                @if(!empty($booking->materials))
                <div class="card mt-3">
                    <div class="card-header"><h5 class="mb-0">Requested Materials</h5></div>
                    <ul class="list-group list-group-flush">
                        @foreach($booking->materials as $m)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>Object #{{ $m->object_id }}</span>
                                <span class="badge bg-secondary">{{ $m->status }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header"><h6 class="mb-0">Actions</h6></div>
                    <div class="card-body d-grid gap-2">
                        @if($booking->status === 'pending')
                            <form method="POST" action="{{ route('research.confirmBooking', $booking->id) }}">@csrf<button class="btn btn-success w-100">Confirm</button></form>
                            <form method="POST" action="{{ route('research.cancelBooking', $booking->id) }}">@csrf<button class="btn btn-danger w-100">Cancel</button></form>
                        @endif
                        @if($booking->status === 'confirmed' && !$booking->checked_in_at)
                            <form method="POST" action="{{ route('research.checkInBooking', $booking->id) }}">@csrf<button class="btn btn-primary w-100">Check In</button></form>
                            <form method="POST" action="{{ route('research.noShowBooking', $booking->id) }}">@csrf<button class="btn btn-warning w-100">No-Show</button></form>
                        @endif
                        @if($booking->checked_in_at && !$booking->checked_out_at && $booking->status !== 'completed')
                            <form method="POST" action="{{ route('research.checkOutBooking', $booking->id) }}">@csrf<button class="btn btn-info w-100">Check Out</button></form>
                        @endif
                        <a href="{{ route('research.bookings') }}" class="btn btn-outline-secondary">Back to Bookings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
