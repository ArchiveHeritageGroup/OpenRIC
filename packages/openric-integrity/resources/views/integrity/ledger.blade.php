@extends('theme::layouts.1col')
@section('title', 'Integrity - Ledger')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-shield-alt me-3"></i><div><h1 class="mb-0">Verification Ledger</h1><span class="small text-muted">Integrity verification history</span></div></div>
<div class="card"><div class="card-header"><h5 class="mb-0">Ledger</h5></div>
<div class="card-body p-0">
    @if(isset($items) && count($items) > 0)
    <table class="table table-striped table-hover mb-0"><thead><tr><th>ID</th><th>Name</th><th>Date</th><th>Status</th></tr></thead>
    <tbody>@foreach($items as $item)<tr><td>{{ $item->id ?? '' }}</td><td>{{ $item->name ?? $item->title ?? '' }}</td><td>{{ $item->created_at ?? $item->verified_at ?? '' }}</td><td>{{ ucfirst($item->status ?? $item->outcome ?? '') }}</td></tr>@endforeach</tbody></table>
    @else<div class="text-center py-4 text-muted">No records found.</div>@endif
</div></div>
<div class="mt-3"><a href="{{ route('integrity.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a></div>
@endsection
