@extends('theme::layouts.1col')
@section('title', 'Integrity - Disposition')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-shield-alt me-3"></i><div><h1 class="mb-0">Disposition</h1><span class="small text-muted">Integrity disposition actions</span></div></div>
<div class="card"><div class="card-header"><h5 class="mb-0">Disposition Actions</h5></div>
<div class="card-body p-0">
    @if(isset($dispositions) && count($dispositions) > 0)
    <table class="table table-striped table-hover mb-0"><thead><tr><th>Object</th><th>Action</th><th>Scheduled</th><th>Status</th></tr></thead>
    <tbody>@foreach($dispositions as $d)<tr><td>#{{ $d->object_id ?? '' }}</td><td>{{ ucfirst($d->action ?? '') }}</td><td>{{ $d->scheduled_date ?? '-' }}</td><td>{{ ucfirst($d->status ?? '') }}</td></tr>@endforeach</tbody></table>
    @else<div class="text-center py-4 text-muted">No disposition records found.</div>@endif
</div></div>
<div class="mt-3"><a href="{{ route('integrity.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a></div>
@endsection
