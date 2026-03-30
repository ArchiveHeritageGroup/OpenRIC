@extends('theme::layouts.1col')
@section('title', 'Researcher Types')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Researcher Types</h2>
        <div class="table-responsive">
            <table class="table table-striped">
                <thead><tr><th>Name</th><th>Description</th><th>Sort Order</th><th>Max Bookings/Day</th><th>Max Items</th></tr></thead>
                <tbody>
                @forelse($types as $t)
                    <tr>
                        <td><strong>{{ $t->name }}</strong></td>
                        <td>{{ $t->description ?? '-' }}</td>
                        <td>{{ $t->sort_order ?? 0 }}</td>
                        <td>{{ $t->max_bookings_per_day ?? '-' }}</td>
                        <td>{{ $t->max_items_per_booking ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center">No researcher types configured.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
