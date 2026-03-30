@extends('theme::layouts.1col')
@section('title', 'Validation Queue')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">AI Extraction Validation Queue</h2>

        <div class="row g-3 mb-4">
            <div class="col-md-2"><div class="card text-center p-3"><h4>{{ $stats['pending'] ?? 0 }}</h4><small>Pending</small></div></div>
            <div class="col-md-2"><div class="card text-center p-3"><h4>{{ $stats['accepted'] ?? 0 }}</h4><small>Accepted</small></div></div>
            <div class="col-md-2"><div class="card text-center p-3"><h4>{{ $stats['rejected'] ?? 0 }}</h4><small>Rejected</small></div></div>
            <div class="col-md-2"><div class="card text-center p-3"><h4>{{ $stats['modified'] ?? 0 }}</h4><small>Modified</small></div></div>
            <div class="col-md-4"><div class="card text-center p-3"><h4>{{ $stats['avg_confidence'] !== null ? number_format($stats['avg_confidence'] * 100, 1) . '%' : '-' }}</h4><small>Avg Confidence</small></div></div>
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Object</th><th>Type</th><th>Confidence</th><th>Status</th><th>Created</th><th></th></tr></thead>
                <tbody>
                @forelse($queue['items'] ?? [] as $item)
                    <tr>
                        <td>{{ $item->object_title ?? 'Object #'.$item->object_id }}</td>
                        <td>{{ $item->result_type ?? '-' }}</td>
                        <td>{{ $item->confidence !== null ? number_format($item->confidence * 100, 1) . '%' : '-' }}</td>
                        <td><span class="badge bg-{{ $item->status === 'pending' ? 'warning' : ($item->status === 'accepted' ? 'success' : 'secondary') }}">{{ ucfirst($item->status) }}</span></td>
                        <td><small>{{ $item->created_at }}</small></td>
                        <td>
                            @if($item->status === 'pending')
                                <button class="btn btn-sm btn-outline-success" onclick="fetch('{{ route('research.validateResult', $item->result_id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}, body:JSON.stringify({form_action:'accept'})}).then(()=>location.reload())">Accept</button>
                                <button class="btn btn-sm btn-outline-danger" onclick="fetch('{{ route('research.validateResult', $item->result_id) }}', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'}, body:JSON.stringify({form_action:'reject'})}).then(()=>location.reload())">Reject</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center">No items in queue.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
