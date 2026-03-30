@extends('theme::layouts.1col')
@section('title', 'Integrity - Schedules')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-shield-alt me-3"></i><div><h1 class="mb-0">Verification Schedules</h1></div></div>
@include('theme::partials.alerts')
<div class="card"><div class="card-header"><h5 class="mb-0">Schedules</h5></div>
<div class="card-body p-0">
    @if(isset($items) && count($items) > 0)
    <table class="table table-striped table-hover mb-0"><thead><tr><th>ID</th><th>Name</th><th>Cron</th><th>Active</th><th>Actions</th></tr></thead>
    <tbody>@foreach($items as $item)<tr><td>{{ $item->id ?? '' }}</td><td>{{ $item->name ?? '' }}</td><td><code>{{ $item->cron_expression ?? '' }}</code></td><td>@if($item->is_active ?? false)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td><td><a href="{{ route('integrity.schedules.edit', $item->id) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a></td></tr>@endforeach</tbody></table>
    @else<div class="text-center py-4 text-muted">No schedules configured.</div>@endif
</div></div>
<div class="mt-3"><a href="{{ route('integrity.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a></div>
@endsection
