@extends('theme::layouts.1col')
@section('title', 'Integrity - Dead Letter')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-shield-alt me-3"></i><div><h1 class="mb-0">Dead Letter Queue</h1><span class="small text-muted">Failed integrity verifications</span></div></div>
<div class="card"><div class="card-header"><h5 class="mb-0">Dead Letter Queue</h5></div>
<div class="card-body p-0">
    @if(isset($deadLetters) && count($deadLetters) > 0)
    <table class="table table-striped table-hover mb-0"><thead><tr><th>ID</th><th>Object</th><th>Failure Type</th><th>Message</th><th>Date</th><th>Status</th></tr></thead>
    <tbody>@foreach($deadLetters as $dl)<tr><td>{{ $dl->id }}</td><td>#{{ $dl->digital_object_id ?? '' }}</td><td>{{ $dl->failure_type ?? '' }}</td><td>{{ \Illuminate\Support\Str::limit($dl->message ?? '', 60) }}</td><td>{{ $dl->created_at ?? '' }}</td><td><span class="badge bg-{{ ($dl->status ?? 'open') === 'open' ? 'danger' : 'success' }}">{{ ucfirst($dl->status ?? 'open') }}</span></td></tr>@endforeach</tbody></table>
    @else<div class="text-center py-4 text-muted">No dead letter entries found.</div>@endif
</div></div>
<div class="mt-3"><a href="{{ route('integrity.dashboard') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Dashboard</a></div>
@endsection
