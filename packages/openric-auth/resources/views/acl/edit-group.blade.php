@extends('theme::layouts.1col')
@section('title', 'Edit Group — ' . ($group->name ?? 'Unnamed'))
@section('content')
<nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('settings.index') }}">Admin</a></li><li class="breadcrumb-item"><a href="{{ route('acl.groups') }}">ACL Groups</a></li><li class="breadcrumb-item active">{{ $group->name ?? 'Unnamed' }}</li></ol></nav>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-gear me-2"></i>{{ $group->name ?? 'Unnamed' }}</h2>
    <a href="{{ route('acl.groups') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Groups</a>
</div>
@include('theme::partials.alerts')
@if($group->description)<p class="text-muted">{{ $group->description }}</p>@endif
<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="bi bi-people me-2"></i>Members ({{ $group->members->count() }})</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead><tr><th>User</th><th>Username</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse($group->members as $member)
                                <tr>
                                    <td>{{ $member->display_name ?? $member->username }}</td>
                                    <td><code>{{ $member->username }}</code></td>
                                    <td class="text-end">
                                        <form action="{{ route('acl.remove-member', ['groupId' => $group->id, 'userId' => $member->user_id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this member?');">@csrf<button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-person-dash"></i></button></form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-center text-muted py-3">No members in this group.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <form action="{{ route('acl.add-member', ['groupId' => $group->id]) }}" method="POST" class="row g-2 align-items-end">@csrf
                    <div class="col"><label for="user_id" class="form-label form-label-sm">Add Member</label><select name="user_id" id="user_id" class="form-select form-select-sm" required><option value="">-- Select User --</option>@foreach($allUsers as $user)<option value="{{ $user->id }}">{{ $user->display_name ?? $user->username }} ({{ $user->username }})</option>@endforeach</select></div>
                    <div class="col-auto"><button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-person-plus me-1"></i>Add</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-primary text-white"><h5 class="mb-0"><i class="bi bi-lock me-2"></i>Permissions ({{ $group->permissions->count() }})</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover mb-0">
                        <thead><tr><th>Action</th><th>Entity Type</th><th>Object IRI</th><th class="text-center">Grant/Deny</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            @forelse($group->permissions as $perm)
                                <tr>
                                    <td><code>{{ $perm->action }}</code></td>
                                    <td>{{ $perm->entity_type ?? '<em class="text-muted">All</em>' }}</td>
                                    <td>{{ $perm->object_iri ?? '<em class="text-muted">All</em>' }}</td>
                                    <td class="text-center">@if($perm->grant_deny)<span class="badge bg-success">Grant</span>@else<span class="badge bg-danger">Deny</span>@endif</td>
                                    <td class="text-end">
                                        <form action="{{ route('acl.edit-group', ['id' => $group->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove this permission?');">@csrf<input type="hidden" name="_action" value="delete_permission"><input type="hidden" name="permission_id" value="{{ $perm->id }}"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">No permissions configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <form action="{{ route('acl.edit-group', ['id' => $group->id]) }}" method="POST" class="row g-2 align-items-end">@csrf<input type="hidden" name="_action" value="add_permission">
                    <div class="col"><label class="form-label form-label-sm">Action</label><input type="text" name="action" class="form-control form-control-sm" placeholder="e.g. read, create" required></div>
                    <div class="col-3"><label class="form-label form-label-sm">Entity Type</label><input type="text" name="entity_type" class="form-control form-control-sm" placeholder="All"></div>
                    <div class="col-3"><label class="form-label form-label-sm">Grant/Deny</label><select name="grant_deny" class="form-select form-select-sm" required><option value="1">Grant</option><option value="0">Deny</option></select></div>
                    <div class="col-auto"><button type="submit" class="btn btn-sm btn-outline-success"><i class="bi bi-plus me-1"></i>Add</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
