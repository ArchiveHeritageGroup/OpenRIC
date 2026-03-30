@extends('theme::layouts.1col')
@section('title', 'Retrieval Queue')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Material Retrieval Queue</h2>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Object</th><th>Researcher</th><th>Booking Date</th><th>Time</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($requests as $r)
                    <tr>
                        <td>{{ $r->object_title ?? 'Object #'.$r->object_id }}</td>
                        <td>{{ $r->first_name }} {{ $r->last_name }}</td>
                        <td>{{ $r->booking_date }}</td>
                        <td>{{ $r->start_time ?? '' }}</td>
                        <td><span class="badge bg-{{ $r->status === 'requested' ? 'warning' : 'info' }}">{{ ucfirst($r->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center">No items in retrieval queue.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
