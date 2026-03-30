@extends('theme::layouts.1col')

@section('title', 'Researcher Permissions')
@section('body-class', 'view researcher-acl')

@php
$userName = is_array($user) ? ($user['name'] ?? $user['username'] ?? 'Unknown') : ($user->name ?? $user->username ?? 'Unknown');
$userSlug = is_array($user) ? ($user['slug'] ?? $user['id'] ?? '') : ($user->slug ?? $user->id ?? '');
@endphp

@section('content')
<div class="container mt-4">
    <h1>Researcher Permissions for {{ $userName }}</h1>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    <p class="text-muted mb-3">Manage researcher-specific permissions for this user.</p>
    
    <form method="POST" action="{{ route('user.editResearcherAcl', $userSlug) }}">
        @csrf
        
        {{-- Researcher-specific settings --}}
        <h3>Researcher Capabilities</h3>
        
        <div class="row g-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="can_request_access" name="can_request_access" 
                        {{ ($researcherPermissions['can_request_access'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="can_request_access">
                        Can request access to restricted materials
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="can_export" name="can_export"
                        {{ ($researcherPermissions['can_export'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="can_export">
                        Can export finding aids
                    </label>
                </div>
            </div>
        </div>
        
        <div class="row g-3 mt-2">
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="can_bookmark" name="can_bookmark"
                        {{ ($researcherPermissions['can_bookmark'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="can_bookmark">
                        Can bookmark items
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="can_comment" name="can_comment"
                        {{ ($researcherPermissions['can_comment'] ?? false) ? 'checked' : '' }}>
                    <label class="form-check-label" for="can_comment">
                        Can add comments to items
                    </label>
                </div>
            </div>
        </div>
        
        {{-- Access restrictions --}}
        <h3 class="mt-4">Access Restrictions</h3>
        
        <div class="row g-3">
            <div class="col-md-6">
                <label for="max_downloads_per_day" class="form-label">Max downloads per day</label>
                <input type="number" class="form-control" id="max_downloads_per_day" name="max_downloads_per_day" 
                    value="{{ $researcherPermissions['max_downloads_per_day'] ?? 10 }}">
            </div>
            <div class="col-md-6">
                <label for="access_expires_at" class="form-label">Access expires</label>
                <input type="date" class="form-control" id="access_expires_at" name="access_expires_at"
                    value="{{ $researcherPermissions['access_expires_at'] ?? '' }}">
            </div>
        </div>
        
        {{-- Allowed repositories for this researcher --}}
        <h3 class="mt-4">Allowed Repositories</h3>
        <p class="text-muted">Select which repositories this researcher can access.</p>
        
        @if(isset($repositories) && !empty($repositories))
            <div class="row">
                @foreach($repositories as $repo)
                    <div class="col-md-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="repo_{{ is_array($repo) ? $repo['id'] : $repo->id }}" 
                                name="allowed_repositories[]" 
                                value="{{ is_array($repo) ? $repo['id'] : $repo->id }}"
                                {{ in_array((is_array($repo) ? $repo['id'] : $repo->id), ($researcherPermissions['allowed_repositories'] ?? [])) ? 'checked' : '' }}>
                            <label class="form-check-label" for="repo_{{ is_array($repo) ? $repo['id'] : $repo->id }}">
                                {{ is_array($repo) ? ($repo['name'] ?? $repo['label'] ?? $repo['id']) : ($repo->name ?? $repo->label ?? $repo->id) }}
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted">No repositories available.</p>
        @endif
        
        <div class="mt-4">
            <button type="submit" class="btn btn-success">Save Researcher Permissions</button>
            <a href="{{ route('user.show', $userSlug) }}" class="btn btn-secondary">Cancel</a>
            <a href="{{ route('user.show', $userSlug) }}" class="btn btn-outline-secondary">Back to User</a>
        </div>
    </form>
</div>
@endsection
