@extends('theme::layouts.1col')
@section('title', 'Taxonomy Audit')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Taxonomy Audit Report</h1>
    <a href="{{ route('reports.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
</div>
@if(isset($results) && count($results) > 0)
    <div class="table-responsive"><table class="table table-bordered table-hover mb-0"><thead><tr>@foreach($columns ?? ['ID', 'Taxonomy', 'Term', 'Created', 'Updated'] as $col)<th>{{ $col }}</th>@endforeach</tr></thead>
    <tbody>@foreach($results as $row)<tr><td>{{ $row->id ?? '' }}</td><td>{{ $row->taxonomy_name ?? '' }}</td><td>{{ $row->term_name ?? '' }}</td><td>{{ $row->created_at ?? '' }}</td><td>{{ $row->updated_at ?? '' }}</td></tr>@endforeach</tbody></table></div>
@else<div class="alert alert-info">No taxonomy audit records found.</div>@endif
@endsection
