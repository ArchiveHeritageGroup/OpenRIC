@extends('theme::layouts.1col')
@section('title', 'Activity Log')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Research Activity Log</h2>
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead><tr><th>Date</th><th>Type</th><th>Entity</th><th>Details</th></tr></thead>
                <tbody>
                @forelse($activities as $a)
                    <tr>
                        <td><small>{{ $a->created_at }}</small></td>
                        <td>{{ $a->activity_type ?? $a->action ?? '-' }}</td>
                        <td>{{ $a->entity_type ?? '' }} {{ $a->entity_id ?? '' }}</td>
                        <td>{{ $a->entity_title ?? $a->description ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">No activity recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
