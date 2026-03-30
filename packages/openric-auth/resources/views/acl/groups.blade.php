@extends('theme::layouts.1col')
@section('title', 'ACL Groups')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0"><i class="bi bi-people me-2"></i>ACL Groups</h1>
</div>
@include('theme::partials.alerts')
<div class="table-responsive">
    <table class="table table-bordered table-striped table-hover mb-0">
        <thead><tr><th>Group</th><th>Members</th><th>Permissions</th></tr></thead>
        <tbody>
            @forelse($groups as $group)
                <tr>
                    <td><a href="{{ route('acl.edit-group', ['id' => $group->id]) }}">{{ $group->name ?? 'Unnamed' }}</a></td>
                    <td>{{ $group->member_count }}</td>
                    <td>{{ $group->permissions_count ?? 0 }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-muted text-center">No groups found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
