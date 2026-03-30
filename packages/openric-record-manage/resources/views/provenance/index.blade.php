@extends('theme::layouts.1col')

@section('title', 'Provenance: ' . ($record->title ?? '[Untitled]'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Provenance Chain</h1>
        <a href="{{ route('record.provenance.timeline', $record->id) }}" class="btn btn-outline-primary btn-sm">Timeline View</a>
    </div>

    <p class="text-muted">Record: <a href="{{ route('records.show', ['iri' => urlencode($record->iri ?? '')]) }}">{{ $record->title ?? '[Untitled]' }}</a></p>

    @include('theme::partials.alerts')

    @if($events->isNotEmpty())
        <div class="list-group mb-4">
            @foreach($events as $event)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">{{ $event->owner_name }}</h6>
                            <small class="text-muted">
                                {{ ucfirst(str_replace('_', ' ', $event->transfer_type ?? 'unknown')) }}
                                @if($event->start_date) &mdash; {{ $event->start_date }}@endif
                                @if($event->end_date) to {{ $event->end_date }}@endif
                            </small>
                            @if($event->certainty && $event->certainty !== 'certain')
                                <span class="badge bg-warning ms-2">{{ ucfirst($event->certainty) }}</span>
                            @endif
                        </div>
                        <div class="btn-group btn-group-sm">
                            <form method="POST" action="{{ route('record.provenance.destroy', $event->id) }}" onsubmit="return confirm('Delete this entry?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </div>
                    @if($event->notes)<p class="mb-0 mt-1 small">{{ $event->notes }}</p>@endif
                </div>
            @endforeach
        </div>
    @else
        <p>No provenance entries recorded yet.</p>
    @endif

    <h5>Add Provenance Entry</h5>
    <form method="POST" action="{{ route('record.provenance.store', $record->id) }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label for="owner_name" class="form-label">Owner Name *</label>
                <input type="text" name="owner_name" id="owner_name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="transfer_type" class="form-label">Transfer Type</label>
                <select name="transfer_type" id="transfer_type" class="form-select">
                    <option value="unknown">Unknown</option>
                    <option value="sale">Sale</option><option value="gift">Gift</option>
                    <option value="donation">Donation</option><option value="bequest">Bequest</option>
                    <option value="purchase">Purchase</option><option value="auction">Auction</option>
                    <option value="creation">Creation</option><option value="accessioning">Accessioning</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="text" name="start_date" id="start_date" class="form-control" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-4">
                <label for="end_date" class="form-label">End Date</label>
                <input type="text" name="end_date" id="end_date" class="form-control" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-4">
                <label for="certainty" class="form-label">Certainty</label>
                <select name="certainty" id="certainty" class="form-select">
                    <option value="certain">Certain</option><option value="probable">Probable</option>
                    <option value="possible">Possible</option><option value="uncertain">Uncertain</option>
                    <option value="unknown">Unknown</option>
                </select>
            </div>
            <div class="col-12">
                <label for="notes" class="form-label">Notes</label>
                <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
            </div>
        </div>
        <button type="submit" class="btn btn-primary mt-3">Add Entry</button>
    </form>
@endsection
