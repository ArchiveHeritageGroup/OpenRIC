@extends('theme::layouts.1col')
@section('title', 'Recent Updates Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Recent Updates Report</h1>
    <a href="{{ route('reports.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
</div>
@include('reports::_filters')
@if(isset($results) && count($results) > 0)
    <div class="table-responsive"><table class="table table-bordered table-hover mb-0"><thead><tr>@foreach($columns ?? ['ID', 'Entity', 'Title', 'Action', 'User', 'Date'] as $col)<th>{{ $col }}</th>@endforeach</tr></thead>
    <tbody>@foreach($results as $row)<tr><td>{{ $row->id ?? '' }}</td><td>{{ $row->class_name ?? $row->entity_type ?? '' }}</td><td>{{ $row->title ?? '' }}</td><td>{{ $row->action ?? 'updated' }}</td><td>{{ $row->username ?? '' }}</td><td>{{ $row->updated_at ?? '' }}</td></tr>@endforeach</tbody></table></div>
@else<div class="alert alert-info">No recent updates found.</div>@endif
@endsection
