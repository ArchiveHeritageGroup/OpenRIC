@extends('theme::layouts.2col')
@section('title', $user->display_name ?? $user->username)
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-person me-2"></i>{{ $user->display_name ?? $user->username }}</h1>
    <div>
        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-outline-primary"><i class="bi bi-pencil me-1"></i>Edit</a>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>
@include('theme::partials.alerts')
<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Account Details</h5></div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-4">Username</dt><dd class="col-sm-8">{{ $user->username }}</dd>
                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $user->email }}</dd>
                    <dt class="col-sm-4">Display Name</dt><dd class="col-sm-8">{{ $user->display_name ?? '-' }}</dd>
                    <dt class="col-sm-4">Active</dt><dd class="col-sm-8">{!! $user->active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' !!}</dd>
                    <dt class="col-sm-4">Created</dt><dd class="col-sm-8">{{ $user->created_at }}</dd>
                    <dt class="col-sm-4">UUID</dt><dd class="col-sm-8"><code class="small">{{ $user->uuid }}</code></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Roles</h5></div>
            <ul class="list-group list-group-flush">
                @forelse($roles as $role)
                    <li class="list-group-item d-flex justify-content-between">{{ $role->label }} <span class="badge bg-primary">Level {{ $role->level }}</span></li>
                @empty
                    <li class="list-group-item text-muted">No roles assigned</li>
                @endforelse
            </ul>
        </div>
        @if($clearance)
            <div class="card mb-3">
                <div class="card-header"><h5 class="mb-0">Security Clearance</h5></div>
                <div class="card-body">
                    <span class="badge" style="background:{{ $clearance->color ?? '#6c757d' }};">{{ $clearance->name ?? 'Unknown' }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
@if(count($recentActivity) > 0)
    <div class="card">
        <div class="card-header"><h5 class="mb-0">Recent Activity</h5></div>
        <div class="table-responsive">
            <table class="table table-sm mb-0">
                <thead><tr><th>Date</th><th>Action</th><th>Entity</th></tr></thead>
                <tbody>
                    @foreach($recentActivity as $entry)
                        <tr><td class="small">{{ $entry->created_at }}</td><td><span class="badge bg-secondary">{{ $entry->action }}</span></td><td>{{ $entry->entity_title ?? $entry->entity_type ?? '-' }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
@endsection
