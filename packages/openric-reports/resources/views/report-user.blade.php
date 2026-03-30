@extends('theme::layouts.1col')
@section('title', 'User Activity Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">User Activity Report</h1>
    <a href="{{ route('reports.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
</div>
@include('reports::_filters')
@if(isset($records) && count($records) > 0)
    <div class="table-responsive"><table class="table table-bordered table-hover mb-0"><thead><tr>@foreach($columns ?? ['User', 'Action', 'Date', 'Identifier', 'Title', 'Repository', 'Area'] as $col)<th>{{ $col }}</th>@endforeach</tr></thead>
    <tbody>@foreach($records as $row)<tr><td>{{ $row->User ?? $row->username ?? '' }}</td><td>{{ $row->Action ?? $row->action ?? '' }}</td><td>{{ $row->Date ?? $row->created_at ?? '' }}</td><td>{{ $row->Identifier ?? $row->entity_id ?? '' }}</td><td>{{ $row->Title ?? $row->entity_type ?? '' }}</td><td>{{ $row->Repository ?? '' }}</td><td>{{ $row->Area ?? $row->action ?? '' }}</td></tr>@endforeach</tbody></table></div>
    @if(method_exists($records, 'links')){{ $records->links() }}@endif
@else<div class="alert alert-info">No user activity records found.</div>@endif
@endsection
