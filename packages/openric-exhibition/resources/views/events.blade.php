@extends('theme::layouts.1col')

@section('title', 'Events — ' . $exhibition->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-calendar-alt me-2"></i>Events</h1>
        <a href="{{ route('exhibition.show', $exhibition->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <p class="text-muted">{{ $exhibition->title }}</p>

    {{-- Add event form --}}
    <div class="card mb-3">
        <div class="card-header"><i class="fas fa-plus me-1"></i> Add Event</div>
        <div class="card-body">
            <form method="POST" action="{{ route('exhibition.events.store', $exhibition->id) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-3">
                        <input type="text" name="title" class="form-control form-control-sm" placeholder="Event title" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Description">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="event_date" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="location" class="form-control form-control-sm" placeholder="Location">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm btn-primary w-100">Add</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if (isset($exhibition->events) && $exhibition->events->isNotEmpty())
    <div class="list-group">
        @foreach ($exhibition->events as $evt)
        <div class="list-group-item d-flex justify-content-between align-items-start">
            <div>
                <strong>{{ $evt->title }}</strong>
                @if ($evt->event_date)
                    <span class="text-muted ms-2"><i class="fas fa-calendar"></i> {{ \Carbon\Carbon::parse($evt->event_date)->format('d M Y') }}{{ $evt->event_time ? ' ' . $evt->event_time : '' }}</span>
                @endif
                @if (!empty($evt->location))
                    <span class="text-muted ms-2"><i class="fas fa-map-marker-alt"></i> {{ $evt->location }}</span>
                @endif
                @if (!empty($evt->description))
                    <br><small class="text-muted">{{ $evt->description }}</small>
                @endif
            </div>
            <form method="POST" action="{{ route('exhibition.events.destroy', [$exhibition->id, $evt->id]) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this event?')"><i class="fas fa-trash"></i></button>
            </form>
        </div>
        @endforeach
    </div>
    @else
        <div class="alert alert-info">No events yet.</div>
    @endif
</div>
@endsection
