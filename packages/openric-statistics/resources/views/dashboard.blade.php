@extends('theme::layouts.1col')

@section('title', 'Statistics Dashboard')

@section('content')
<h1>Statistics Dashboard</h1>

{{-- Date range filter --}}
<form method="GET" action="{{ route('statistics.dashboard') }}" class="mb-4">
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
      <button type="submit" class="btn btn-primary">Filter</button>
    </div>
    <div class="col-auto ms-auto">
      <a href="{{ route('statistics.export', ['type' => 'dashboard', 'start' => $startDate, 'end' => $endDate]) }}" class="btn btn-outline-secondary">Export CSV</a>
    </div>
  </div>
</form>

{{-- Summary cards --}}
<div class="row mb-4">
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h5 class="card-title text-muted">Views</h5>
        <p class="display-6">{{ number_format($stats['views']) }}</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h5 class="card-title text-muted">Downloads</h5>
        <p class="display-6">{{ number_format($stats['downloads']) }}</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h5 class="card-title text-muted">Searches</h5>
        <p class="display-6">{{ number_format($stats['searches']) }}</p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card text-center">
      <div class="card-body">
        <h5 class="card-title text-muted">Unique Visitors</h5>
        <p class="display-6">{{ number_format($stats['unique_visitors']) }}</p>
      </div>
    </div>
  </div>
</div>

{{-- Views over time chart --}}
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Views Over Time</strong>
    <a href="{{ route('statistics.views', ['start' => $startDate, 'end' => $endDate]) }}" class="btn btn-sm btn-outline-primary">Details</a>
  </div>
  <div class="card-body">
    <canvas id="viewsChart" height="80"></canvas>
  </div>
</div>

{{-- Downloads over time chart --}}
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Downloads Over Time</strong>
    <a href="{{ route('statistics.downloads', ['start' => $startDate, 'end' => $endDate]) }}" class="btn btn-sm btn-outline-primary">Details</a>
  </div>
  <div class="card-body">
    <canvas id="downloadsChart" height="80"></canvas>
  </div>
</div>

<div class="row">
  {{-- Top viewed items --}}
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Top Viewed Items</strong>
        <a href="{{ route('statistics.topItems', ['type' => 'view', 'start' => $startDate, 'end' => $endDate]) }}" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Entity</th>
              <th class="text-end">Views</th>
              <th class="text-end">Unique</th>
            </tr>
          </thead>
          <tbody>
            @forelse($topItems as $i => $item)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>
                <a href="{{ route('statistics.entity', ['iri' => $item->entity_iri, 'start' => $startDate, 'end' => $endDate]) }}" title="{{ $item->entity_iri }}">
                  {{ \Illuminate\Support\Str::limit($item->entity_iri, 60) }}
                </a>
                <br><small class="text-muted">{{ $item->entity_type }}</small>
              </td>
              <td class="text-end">{{ number_format($item->event_count) }}</td>
              <td class="text-end">{{ number_format($item->unique_visitors) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted py-3">No data for this period.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Top downloaded items --}}
  <div class="col-md-6 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Top Downloaded Items</strong>
        <a href="{{ route('statistics.topItems', ['type' => 'download', 'start' => $startDate, 'end' => $endDate]) }}" class="btn btn-sm btn-outline-primary">View All</a>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
          <thead>
            <tr>
              <th>#</th>
              <th>Entity</th>
              <th class="text-end">Downloads</th>
              <th class="text-end">Unique</th>
            </tr>
          </thead>
          <tbody>
            @forelse($topDownloads as $i => $item)
            <tr>
              <td>{{ $i + 1 }}</td>
              <td>
                <a href="{{ route('statistics.entity', ['iri' => $item->entity_iri, 'start' => $startDate, 'end' => $endDate]) }}" title="{{ $item->entity_iri }}">
                  {{ \Illuminate\Support\Str::limit($item->entity_iri, 60) }}
                </a>
                <br><small class="text-muted">{{ $item->entity_type }}</small>
              </td>
              <td class="text-end">{{ number_format($item->event_count) }}</td>
              <td class="text-end">{{ number_format($item->unique_visitors) }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted py-3">No data for this period.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- Geographic summary --}}
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Geographic Breakdown (Top 10)</strong>
    <a href="{{ route('statistics.geographic', ['start' => $startDate, 'end' => $endDate]) }}" class="btn btn-sm btn-outline-primary">View All</a>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0">
      <thead>
        <tr>
          <th>Country</th>
          <th class="text-end">Events</th>
          <th class="text-end">Unique Visitors</th>
          <th class="text-end">Unique Entities</th>
        </tr>
      </thead>
      <tbody>
        @forelse($geoStats as $geo)
        <tr>
          <td>{{ $geo->country }}</td>
          <td class="text-end">{{ number_format($geo->event_count) }}</td>
          <td class="text-end">{{ number_format($geo->unique_visitors) }}</td>
          <td class="text-end">{{ number_format($geo->unique_entities) }}</td>
        </tr>
        @empty
        <tr><td colspan="4" class="text-center text-muted py-3">No geographic data for this period.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

{{-- Navigation --}}
<div class="d-flex gap-2 mb-4">
  <a href="{{ route('statistics.admin') }}" class="btn btn-outline-secondary">Settings</a>
  <a href="{{ route('statistics.bots') }}" class="btn btn-outline-secondary">Bot Management</a>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Views chart
    var viewsData = @json($viewsOverTime);
    new Chart(document.getElementById('viewsChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: viewsData.map(function (r) { return r.period; }),
            datasets: [{
                label: 'Views',
                data: viewsData.map(function (r) { return r.event_count; }),
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13,110,253,0.1)',
                fill: true,
                tension: 0.3
            }, {
                label: 'Unique Visitors',
                data: viewsData.map(function (r) { return r.unique_visitors; }),
                borderColor: '#6c757d',
                borderDash: [5,5],
                fill: false,
                tension: 0.3
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // Downloads chart
    var dlData = @json($downloadsOverTime);
    new Chart(document.getElementById('downloadsChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: dlData.map(function (r) { return r.period; }),
            datasets: [{
                label: 'Downloads',
                data: dlData.map(function (r) { return r.event_count; }),
                backgroundColor: 'rgba(25,135,84,0.6)',
                borderColor: '#198754',
                borderWidth: 1
            }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });
});
</script>
@endpush
