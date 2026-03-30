@extends('theme::layouts.1col')
@section('title', 'Reproduction Requests')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Reproduction Requests</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="d-flex gap-2 mb-3">
            @foreach(['' => 'All', 'draft' => 'Draft', 'submitted' => 'Submitted', 'processing' => 'Processing', 'completed' => 'Completed'] as $k => $v)
                <a href="{{ route('research.reproductions', ['status' => $k]) }}" class="btn btn-sm {{ request('status') === $k ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $v }}</a>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Reference</th><th>Purpose</th><th>Status</th><th>Created</th></tr></thead>
                <tbody>
                @forelse($requests as $r)
                    <tr>
                        <td>{{ $r->reference_number ?? '#'.$r->id }}</td>
                        <td>{{ Str::limit($r->purpose ?? '-', 60) }}</td>
                        <td><span class="badge bg-{{ $r->status === 'completed' ? 'success' : ($r->status === 'draft' ? 'secondary' : 'info') }}">{{ ucfirst($r->status) }}</span></td>
                        <td><small>{{ $r->created_at }}</small></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">No reproduction requests.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
