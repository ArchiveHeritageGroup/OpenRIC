@extends('theme::layouts.2col')
@section('title', 'Users')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-people me-2"></i>Users</h1>
    <a href="{{ route('admin.users.create') }}" class="btn btn-success"><i class="bi bi-plus me-1"></i>Create User</a>
</div>
@include('theme::partials.alerts')
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-auto">
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="active" {{ ($status ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ ($status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="all" {{ ($status ?? '') === 'all' ? 'selected' : '' }}>All</option>
                </select>
            </div>
            <div class="col">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search users..." value="{{ request('q') }}">
            </div>
            <div class="col-auto"><button class="btn btn-sm btn-outline-primary" type="submit">Filter</button></div>
        </form>
    </div>
</div>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead><tr><th>Username</th><th>Email</th><th>Display Name</th><th>Roles</th><th>Active</th><th>Actions</th></tr></thead>
        <tbody>
            @forelse($users as $user)
                <tr>
                    <td><a href="{{ route('admin.users.show', $user->id) }}">{{ $user->username }}</a></td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->display_name ?? '-' }}</td>
                    <td><span class="badge bg-secondary">{{ $user->role_names ?? 'None' }}</span></td>
                    <td>{!! $user->active ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>' !!}</td>
                    <td>
                        <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                        <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" class="d-inline" onsubmit="return confirm('Deactivate this user?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-muted text-center">No users found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
{{ $users->links() }}
@endsection
