@extends('theme::layouts.1col')

@section('title', 'Edit Actor Permissions')
@section('body-class', 'view actor-acl')

@php
$userName = is_array($user) ? ($user['name'] ?? $user['username'] ?? 'Unknown') : ($user->name ?? $user->username ?? 'Unknown');
$userSlug = is_array($user) ? ($user['slug'] ?? $user['id'] ?? '') : ($user->slug ?? $user->id ?? '');
$actorList = $actors ?? [];
@endphp

@section('content')
<div class="container mt-4">
    <h1>Edit Actor Permissions for {{ $userName }}</h1>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    <p class="text-muted mb-3">Grant or deny specific actor permissions for this user.</p>
    
    <form method="POST" action="{{ route('user.editActorAcl', $userSlug) }}">
        @csrf
        
        {{-- Current permissions --}}
        <h3>Current Permissions</h3>
        @if(isset($permissions) && $permissions->count() > 0)
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Object</th>
                        <th>Action</th>
                        <th>Grant/Deny</th>
                        <th>Change</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $perm)
                        <tr>
                            <td>{{ $perm->object_name ?? $perm->object_id ?? 'N/A' }}</td>
                            <td>{{ $perm->action ?? 'N/A' }}</td>
                            <td>
                                @if($perm->grant_deny ?? false)
                                    <span class="badge bg-success">Granted</span>
                                @else
                                    <span class="badge bg-danger">Denied</span>
                                @endif
                            </td>
                            <td>
                                <select name="permissions[{{ $perm->id }}]" class="form-select form-select-sm">
                                    <option value="keep">Keep</option>
                                    <option value="grant">Grant</option>
                                    <option value="deny">Deny</option>
                                    <option value="inherit">Remove</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-muted">No specific actor permissions currently set.</p>
        @endif
        
        {{-- Add new permission --}}
        <h3 class="mt-4">Add New Permission</h3>
        <div class="row g-3">
            <div class="col-md-4">
                <label for="new_actor_id" class="form-label">Actor</label>
                <select name="new_actor_id" id="new_actor_id" class="form-select">
                    <option value="">-- Select Actor --</option>
                    @foreach($actorList as $actor)
                        <option value="{{ is_array($actor) ? $actor['id'] : $actor->id }}">
                            {{ is_array($actor) ? ($actor['authorized_form_of_name'] ?? $actor['id']) : ($actor->authorized_form_of_name ?? $actor->id) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="new_action" class="form-label">Action</label>
                <select name="new_action" id="new_action" class="form-select">
                    <option value="read">Read</option>
                    <option value="create">Create</option>
                    <option value="update">Update</option>
                    <option value="delete">Delete</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="new_grant_deny" class="form-label">Type</label>
                <select name="new_grant_deny" id="new_grant_deny" class="form-select">
                    <option value="grant">Grant</option>
                    <option value="deny">Deny</option>
                </select>
            </div>
        </div>
        
        <div class="mt-4">
            <button type="submit" class="btn btn-success">Save Permissions</button>
            <a href="{{ route('user.indexActorAcl', $userSlug) }}" class="btn btn-secondary">Cancel</a>
            <a href="{{ route('user.show', $userSlug) }}" class="btn btn-outline-secondary">Back to User</a>
        </div>
    </form>
</div>
@endsection
