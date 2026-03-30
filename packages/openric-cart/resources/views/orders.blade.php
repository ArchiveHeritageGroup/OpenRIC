@extends('openric-theme::layouts.1col')

@section('title', 'My Orders')

@section('content')
<div class="container mt-4">
    <h2><i class="fas fa-receipt me-2"></i>Your Orders</h2>
    
    @if(empty($orders))
        <div class="alert alert-info mt-4">You haven't placed any orders yet.</div>
    @else
        <table class="table mt-4">
            <thead>
                <tr>
                    <th>Order IRI</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td><code>{{ $order['order']['value'] ?? $order['order'] }}</code></td>
                    <td><span class="badge bg-{{ $order['status']['value'] ?? 'secondary' }}">{{ $order['status']['value'] ?? 'pending' }}</span></td>
                    <td>{{ $order['created']['value'] ?? $order['created'] ?? 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
