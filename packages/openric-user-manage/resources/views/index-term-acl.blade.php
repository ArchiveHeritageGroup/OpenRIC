@extends('theme::layouts.1col')

@section('title', 'Taxonomy Permissions')
@section('body-class', 'view term-acl')

@php
$userName = is_array($user) ? ($user['name'] ?? $user['username'] ?? 'Unknown') : ($user->name ?? $user->username ?? 'Unknown');
$userSlug = is_array($user) ? ($user['slug'] ?? $user['id'] ?? '') : ($user->slug ?? $user->id ?? '');
$permissions = $acl ?? [];
$objectNames = $objectNames ?? [];
@endphp

@section('content')
<div class="container mt-4">
    <h1>Taxonomy Permissions for {{ $userName }}</h1>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    <p class="text-muted mb-3">Manage taxonomy (controlled vocabulary) permissions for this user.</p>
    
    @auth
    <div class="mb-3">
        <a href="{{ route('user.editTermAcl', $userSlug) }}" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit Permissions
        </a>
        <a href="{{ route('user.show', $userSlug) }}" class="btn btn-secondary">Back to User</a>
    </div>
    @endauth
    
    @if(empty($permissions))
        <div class="alert alert-info">
            No specific taxonomy permissions defined for this user. Permissions may be inherited from their role(s).
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Taxonomy/Vocabulary</th>
                        <th>Permission</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($permissions as $objectIri => $actions)
                        @foreach($actions as $action => $perm)
                            <tr>
                                <td>{{ $objectNames[$objectIri] ?? $objectIri }}</td>
                                <td>{{ ucfirst($action) }}</td>
                                <td>
                                    @if(is_object($perm) ? $perm->grant_deny : ($perm['grant_deny'] ?? false))
                                        <span class="badge bg-success">Granted</span>
                                    @else
                                        <span class="badge bg-danger">Denied</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
