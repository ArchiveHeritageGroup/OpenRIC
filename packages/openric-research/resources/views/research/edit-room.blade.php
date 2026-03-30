@extends('theme::layouts.1col')
@section('title', ($isNew ?? true) ? 'Add Reading Room' : 'Edit Reading Room')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">{{ ($isNew ?? true) ? 'Add Reading Room' : 'Edit Reading Room' }}</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <form method="POST" action="{{ route('research.editRoom') }}">
            @csrf
            @if($room ?? null)<input type="hidden" name="id" value="{{ $room->id }}">@endif
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" required value="{{ $room->name ?? '' }}"></div>
                        <div class="col-md-4"><label class="form-label">Code</label><input type="text" name="code" class="form-control" value="{{ $room->code ?? '' }}"></div>
                        <div class="col-md-4"><label class="form-label">Location</label><input type="text" name="location" class="form-control" value="{{ $room->location ?? '' }}"></div>
                        <div class="col-md-3"><label class="form-label">Capacity</label><input type="number" name="capacity" class="form-control" value="{{ $room->capacity ?? 10 }}"></div>
                        <div class="col-md-3"><label class="form-label">Opening Time</label><input type="time" name="opening_time" class="form-control" value="{{ $room->opening_time ?? '09:00:00' }}"></div>
                        <div class="col-md-3"><label class="form-label">Closing Time</label><input type="time" name="closing_time" class="form-control" value="{{ $room->closing_time ?? '17:00:00' }}"></div>
                        <div class="col-md-3"><label class="form-label">Days Open</label><input type="text" name="days_open" class="form-control" value="{{ $room->days_open ?? 'Mon,Tue,Wed,Thu,Fri' }}"></div>
                        <div class="col-md-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ $room->description ?? '' }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Amenities</label><textarea name="amenities" class="form-control" rows="2">{{ $room->amenities ?? '' }}</textarea></div>
                        <div class="col-md-6"><label class="form-label">Rules</label><textarea name="rules" class="form-control" rows="2">{{ $room->rules ?? '' }}</textarea></div>
                        <div class="col-md-3"><label class="form-label">Advance Booking (days)</label><input type="number" name="advance_booking_days" class="form-control" value="{{ $room->advance_booking_days ?? 14 }}"></div>
                        <div class="col-md-3"><label class="form-label">Max Hours</label><input type="number" name="max_booking_hours" class="form-control" value="{{ $room->max_booking_hours ?? 4 }}"></div>
                        <div class="col-md-3"><label class="form-label">Cancellation (hours)</label><input type="number" name="cancellation_hours" class="form-control" value="{{ $room->cancellation_hours ?? 24 }}"></div>
                        <div class="col-md-3"><div class="form-check mt-4"><input type="checkbox" name="is_active" class="form-check-input" value="1" {{ ($room->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label">Active</label></div></div>
                    </div>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">{{ ($isNew ?? true) ? 'Create' : 'Update' }} Room</button>
                <a href="{{ route('research.rooms') }}" class="btn btn-outline-secondary ms-2">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
