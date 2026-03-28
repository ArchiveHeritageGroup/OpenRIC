@extends('theme::layouts.1col')

@section('title', 'Top Items — Statistics')

@section('content')
<h1>Top Items</h1>

{{-- Date range and type filter --}}
<form method="GET" action="{{ route('statistics.topItems') }}" class="mb-4">
  <div class="row g-2 align-items-end">
    <div class="col-auto">
      <label for="start" class="form-label">From</label>
      <input type="date" id="start" name="start" class="form-control" value="{{ $startDate }}">
    </div>
    <div class="col-auto">
      <label for="end" class="form-label">To</label>
      <input type="date" id="end" name="end" class="form-control" value="{{ $endDate }}">
    </div>
    <div class="col-auto">
      <label for="type" class="form-label">Event Type</label>
      <select id="type" name="type" class="form-select">
        <option value="view" @if($eventType === 'view') selected @endif>Views</option>
        <option value="download" @if($eventType === 'download') selected @endif>Downloads</option>
        <option value="search" @if($eventType === 'search') selected @endif>Searches</option>
      </select>
    </div>
    <div class="col-auto">
      <label for="limit" class="form-label">Limit</label>
      <input type="number" id="limit" name="limit" class="form-control" value="{{ $limit }}" min="1" max="500" style="width:80px">
    </div>
    <div class="col-auto">
      <button type="submit" class="btn btn-primary">Filter</button>
    </div>
    <div class="col-auto ms-auto">
      <a href="{{ route('statistics.export', ['type' => 'top-' . $eventType . 's', 'start' => $startDate, 'end' => $endDate]) }}" class="btn btn-outline-secondary">Export CSV</a>
    </div>
  </div>
</form>

{{-- Results table --}}
<div class="card">
  <div class="card-header">
    <strong>Top {{ ucfirst($eventType) }}ed Items</strong>
    <span class="text-muted ms-2">({{ $startDate }} to {{ $endDate }})</span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0">
      <thead>
        <tr>
          <th style="width:40px">#</th>
          <th>Entity IRI</th>
          <th>Type</th>
          <th class="text-end">{{ ucfirst($eventType) }}s</th>
          <th class="text-end">Unique Visitors</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $i => $item)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>
            <a href="{{ route('statistics.entity', ['iri' => $item->entity_iri, 'start' => $startDate, 'end' => $endDate]) }}" title="{{ $item->entity_iri }}">
              {{ \Illuminate\Support\Str::limit($item->entity_iri, 80) }}
            </a>
          </td>
          <td><span class="badge bg-secondary">{{ $item->entity_type }}</span></td>
          <td class="text-end">{{ number_format($item->event_count) }}</td>
          <td class="text-end">{{ number_format($item->unique_visitors) }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-3">No data for this period.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  <a href="{{ route('statistics.dashboard', ['start' => $startDate, 'end' => $endDate]) }}" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>
@endsection
