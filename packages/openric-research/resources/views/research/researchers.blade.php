@extends('theme::layouts.1col')
@section('title', 'Manage Researchers')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Manage Researchers</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="d-flex gap-2 mb-3 flex-wrap">
            @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'suspended' => 'Suspended', 'expired' => 'Expired'] as $k => $v)
                <a href="{{ route('research.researchers', ['filter' => $k]) }}" class="btn btn-sm {{ ($filter ?? 'all') === $k ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $v }} ({{ $counts[$k] ?? 0 }})</a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('research.researchers') }}" class="mb-3">
            <input type="hidden" name="filter" value="{{ $filter ?? 'all' }}">
            <div class="input-group" style="max-width:400px;">
                <input type="text" name="q" class="form-control" placeholder="Search researchers..." value="{{ $query ?? '' }}">
                <button class="btn btn-outline-primary" type="submit">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Name</th><th>Email</th><th>Institution</th><th>Status</th><th>Created</th><th></th></tr></thead>
                <tbody>
                @forelse($researchers as $r)
                    <tr>
                        <td><a href="{{ route('research.viewResearcher', $r->id) }}">{{ $r->first_name }} {{ $r->last_name }}</a></td>
                        <td>{{ $r->email }}</td>
                        <td>{{ $r->institution ?? '-' }}</td>
                        <td><span class="badge bg-{{ $r->status === 'approved' ? 'success' : ($r->status === 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($r->status) }}</span></td>
                        <td><small>{{ $r->created_at }}</small></td>
                        <td><a href="{{ route('research.viewResearcher', $r->id) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted text-center">No researchers found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
