@extends('theme::layouts.1col')

@section('title', 'Activity Log')
@section('body-class', 'view activity')

@php
$userName = is_array($user) ? ($user['name'] ?? $user['username'] ?? 'Unknown') : ($user->name ?? $user->username ?? 'Unknown');
$userSlug = is_array($user) ? ($user['slug'] ?? $user['id'] ?? '') : ($user->slug ?? $user->id ?? '');
$activities = $activity ?? collect();
$activities = is_array($activities) ? collect($activities) : $activities;
@endphp

@section('content')
<div class="container mt-4">
    <h1>Activity Log for {{ $userName }}</h1>
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    
    <p class="text-muted mb-3">Recent activity for this user account.</p>
    
    <div class="mb-3">
        <a href="{{ route('user.show', $userSlug) }}" class="btn btn-secondary">Back to User</a>
    </div>
    
    @if($activities->isEmpty())
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No activity recorded for this user.
        </div>
    @else
        <div class="table-responsive">
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Date/Time</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $activity)
                        <tr>
                            <td>
                                @if(is_array($activity))
                                    {{ \Carbon\Carbon::parse($activity['created_at'] ?? $activity['timestamp'] ?? now())->format('Y-m-d H:i:s') }}
                                @else
                                    {{ \Carbon\Carbon::parse($activity->created_at ?? $activity->timestamp ?? now())->format('Y-m-d H:i:s') }}
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    {{ is_array($activity) ? ($activity['action'] ?? 'Unknown') : ($activity->action ?? 'Unknown') }}
                                </span>
                            </td>
                            <td>
                                {{ is_array($activity) ? ($activity['description'] ?? $activity['details'] ?? '') : ($activity->description ?? $activity->details ?? '') }}
                            </td>
                            <td class="text-muted small">
                                {{ is_array($activity) ? ($activity['ip_address'] ?? '-') : ($activity->ip_address ?? '-') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <div class="mt-3">
            <p class="text-muted">Total activities: {{ $activities->count() }}</p>
        </div>
    @endif
</div>
@endsection
