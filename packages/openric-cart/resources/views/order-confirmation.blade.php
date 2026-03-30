@extends('openric-theme::layouts.1col')

@section('title', 'Order Confirmation')

@section('content')
<div class="container mt-4 text-center">
    <div class="alert alert-success">
        <i class="fas fa-check-circle fa-5x mb-3"></i>
        <h2>Thank You!</h2>
        <p>Your order has been placed successfully.</p>
        <p class="mb-0">Order IRI: {{ $orderIri }}</p>
    </div>
    
    <div class="mt-4">
        <a href="{{ route('cart.orders') }}" class="btn btn-primary">View Your Orders</a>
        <a href="{{ route('cart.browse') }}" class="btn btn-outline-secondary">Continue Shopping</a>
    </div>
</div>
@endsection
