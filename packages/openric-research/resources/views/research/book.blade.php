@extends('theme::layouts.1col')
@section('title', 'Book a Visit')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Book a Visit</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{!! session('error') !!}</div>@endif

        <form method="POST" action="{{ route('research.book') }}">
            @csrf
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Reading Room *</label>
                            <select name="reading_room_id" class="form-select" required>
                                <option value="">-- Select room --</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}">{{ $room->name }} ({{ $room->location ?? '' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date *</label>
                            <input type="date" name="booking_date" class="form-control" required min="{{ date('Y-m-d') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" required value="09:00">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Time *</label>
                            <input type="time" name="end_time" class="form-control" required value="12:00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Purpose</label>
                            <input type="text" name="purpose" class="form-control" placeholder="Research purpose">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Special requirements or notes"></textarea>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Submit Booking</button>
                <a href="{{ route('research.bookings') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
