@extends('theme::layouts.1col')

@section('title', 'Geographic Breakdown — Statistics')

@section('content')
<h1>Geographic Breakdown</h1>

{{-- Date range filter --}}
<form method="GET" action="{{ route('statistics.geographic') }}" class="mb-4">
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
      <a href="{{ route('statistics.export', ['type' => 'geographic', 'start' => $startDate, 'end' => $endDate]) }}" class="btn btn-outline-secondary">Export CSV</a>
    </div>
  </div>
</form>

{{-- Chart --}}
<div class="card mb-4">
  <div class="card-header"><strong>Events by Country</strong></div>
  <div class="card-body">
    <canvas id="geoChart" height="100"></canvas>
  </div>
</div>

{{-- Table --}}
<div class="card">
  <div class="card-header">
    <strong>All Countries</strong>
    <span class="text-muted ms-2">({{ $startDate }} to {{ $endDate }})</span>
  </div>
  <div class="card-body p-0">
    <table class="table table-sm table-hover mb-0">
      <thead>
        <tr>
          <th>#</th>
          <th>Country</th>
          <th class="text-end">Events</th>
          <th class="text-end">Unique Visitors</th>
          <th class="text-end">Unique Entities</th>
          <th class="text-end">% of Total</th>
        </tr>
      </thead>
      <tbody>
        @php
          $totalEvents = collect($data)->sum(fn ($r) => $r->event_count ?? 0);
        @endphp
        @forelse($data as $i => $row)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $row->country }}</td>
          <td class="text-end">{{ number_format($row->event_count) }}</td>
          <td class="text-end">{{ number_format($row->unique_visitors) }}</td>
          <td class="text-end">{{ number_format($row->unique_entities) }}</td>
          <td class="text-end">
            @if($totalEvents > 0)
              {{ number_format(($row->event_count / $totalEvents) * 100, 1) }}%
            @else
              0%
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="6" class="text-center text-muted py-3">No geographic data for this period.</td></tr>
        @endforelse
      </tbody>
      @if(count($data) > 0)
      <tfoot>
        <tr class="fw-bold">
          <td colspan="2">Total</td>
          <td class="text-end">{{ number_format($totalEvents) }}</td>
          <td class="text-end">{{ number_format(collect($data)->sum(fn ($r) => $r->unique_visitors ?? 0)) }}</td>
          <td class="text-end">{{ number_format(collect($data)->sum(fn ($r) => $r->unique_entities ?? 0)) }}</td>
          <td class="text-end">100%</td>
        </tr>
      </tfoot>
      @endif
    </table>
  </div>
</div>

<div class="mt-3">
  <a href="{{ route('statistics.dashboard', ['start' => $startDate, 'end' => $endDate]) }}" class="btn btn-outline-secondary">Back to Dashboard</a>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var geoData = @json($data);
    var top15 = geoData.slice(0, 15);

    new Chart(document.getElementById('geoChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: top15.map(function (r) { return r.country; }),
            datasets: [{
                label: 'Events',
                data: top15.map(function (r) { return r.event_count; }),
                backgroundColor: 'rgba(13,110,253,0.6)',
                borderColor: '#0d6efd',
                borderWidth: 1
            }, {
                label: 'Unique Visitors',
                data: top15.map(function (r) { return r.unique_visitors; }),
                backgroundColor: 'rgba(108,117,125,0.4)',
                borderColor: '#6c757d',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            indexAxis: 'y',
            plugins: { legend: { position: 'bottom' } },
            scales: { x: { beginAtZero: true } }
        }
    });
});
</script>
@endpush
