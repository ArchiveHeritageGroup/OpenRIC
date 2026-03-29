@extends('theme::layouts.1col')
@section('title', $reportName ?? 'Report')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <h1><i class="fas fa-chart-bar me-2"></i>{{ $reportName ?? 'Report' }}</h1>

    @if(!empty($summary))
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">Summary</div>
      <div class="card-body">
        <table class="table table-sm mb-0">
          @foreach($summary as $key => $value)
          <tr><th>{{ ucfirst(str_replace('_', ' ', $key)) }}</th><td>{{ $value }}</td></tr>
          @endforeach
        </table>
      </div>
    </div>
    @endif

    <div class="card">
      <div class="card-body">
        @if(!empty($results))
          <div class="table-responsive">
            <table class="table table-bordered table-sm table-striped">
              <thead><tr>@foreach(array_keys((array) $results[0]) as $col)<th>{{ ucfirst($col) }}</th>@endforeach</tr></thead>
              <tbody>
                @foreach($results as $row)
                <tr>@foreach((array) $row as $val)<td>{{ $val }}</td>@endforeach</tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <p class="text-muted text-center py-4">No data available for this report.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
