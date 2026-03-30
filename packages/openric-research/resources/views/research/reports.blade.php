@extends('theme::layouts.1col')
@section('title', 'Research Reports')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Research Reports</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        <div class="d-flex gap-2 mb-3">
            @foreach(['' => 'All', 'draft' => 'Draft', 'in_progress' => 'In Progress', 'published' => 'Published'] as $k => $v)
                <a href="{{ route('research.reports', ['status' => $k]) }}" class="btn btn-sm {{ ($currentStatus ?? '') === $k ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $v }}</a>
            @endforeach
        </div>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Title</th><th>Template</th><th>Status</th><th>Updated</th><th></th></tr></thead>
                <tbody>
                @forelse($reports as $r)
                    <tr>
                        <td><a href="{{ route('research.viewReport', $r->id) }}">{{ $r->title }}</a></td>
                        <td>{{ $r->template_type ?? 'custom' }}</td>
                        <td><span class="badge bg-secondary">{{ ucfirst($r->status ?? 'draft') }}</span></td>
                        <td><small>{{ $r->updated_at ?? $r->created_at }}</small></td>
                        <td><a href="{{ route('research.viewReport', $r->id) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center">No reports.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
