@extends('theme::layouts.1col')
@section('title', 'Notifications')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Notifications</h2>
            <form method="POST" action="{{ route('research.notifications') }}">@csrf <input type="hidden" name="do" value="mark_all_read"><button class="btn btn-outline-primary btn-sm">Mark All Read</button></form>
        </div>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

        @forelse($notifications as $n)
            <div class="card mb-2 {{ ($n->is_read ?? false) ? '' : 'border-primary' }}">
                <div class="card-body d-flex justify-content-between align-items-start">
                    <div>
                        @if(!($n->is_read ?? false))<span class="badge bg-primary me-2">New</span>@endif
                        <strong>{{ $n->title ?? $n->notification_type ?? 'Notification' }}</strong>
                        <p class="mb-0 text-muted">{{ $n->message ?? $n->content ?? '' }}</p>
                        <small class="text-muted">{{ $n->created_at }}</small>
                    </div>
                    @if(!($n->is_read ?? false))
                        <form method="POST" action="{{ route('research.notifications') }}">@csrf <input type="hidden" name="do" value="mark_read"><input type="hidden" name="id" value="{{ $n->id }}"><button class="btn btn-sm btn-outline-secondary">Read</button></form>
                    @endif
                </div>
            </div>
        @empty
            <p class="text-muted">No notifications.</p>
        @endforelse
    </div>
</div>
@endsection
