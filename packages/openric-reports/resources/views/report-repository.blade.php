@extends('theme::layouts.1col')
@section('title', 'Repository Report')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Repository Report</h1>
    <a href="{{ route('reports.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
</div>
@include('reports::_filters')
@if(isset($results) && count($results) > 0)
    <div class="table-responsive"><table class="table table-bordered table-hover mb-0"><thead><tr>@foreach($columns ?? ['ID', 'Identifier', 'Name', 'Created', 'Updated'] as $col)<th>{{ $col }}</th>@endforeach</tr></thead>
    <tbody>@foreach($results as $row)<tr><td>{{ $row->id ?? '' }}</td><td>{{ $row->identifier ?? '' }}</td><td>{{ $row->authorized_form_of_name ?? $row->name ?? '' }}</td><td>{{ $row->created_at ?? '' }}</td><td>{{ $row->updated_at ?? '' }}</td></tr>@endforeach</tbody></table></div>
    @if(method_exists($results, 'links')){{ $results->links() }}@endif
@else<div class="alert alert-info">No repository records found.</div>@endif
@endsection
