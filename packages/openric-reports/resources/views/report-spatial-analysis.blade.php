@extends('theme::layouts.1col')
@section('title', 'Spatial Analysis')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Spatial Analysis Report</h1>
    <a href="{{ route('reports.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
</div>
@if(isset($results) && count($results) > 0)
    <div class="table-responsive"><table class="table table-bordered table-hover mb-0"><thead><tr>@foreach($columns ?? ['ID', 'Identifier', 'Title', 'Latitude', 'Longitude'] as $col)<th>{{ $col }}</th>@endforeach</tr></thead>
    <tbody>@foreach($results as $row)<tr><td>{{ $row->id ?? '' }}</td><td>{{ $row->identifier ?? '' }}</td><td>{{ $row->title ?? '' }}</td><td>{{ $row->latitude ?? '' }}</td><td>{{ $row->longitude ?? '' }}</td></tr>@endforeach</tbody></table></div>
@else<div class="alert alert-info">No spatial records found.</div>@endif
@endsection
