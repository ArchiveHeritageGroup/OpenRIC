@extends('openric-theme::layouts.1col')

@section('title', 'Shopping Cart')

@section('content')
<div class="container mt-4">
    <h2><i class="fas fa-shopping-cart me-2"></i>Your Cart</h2>
    
    @if(session('notice'))
        <div class="alert alert-info">{{ session('notice') }}</div>
    @endif
    
    @if(empty($cart['items']))
        <div class="alert alert-info mt-4">Your cart is empty.</div>
    @else
        <table class="table mt-4">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($cart['items'] as $item)
                <tr>
                    <td>{{ $item['product']['value'] ?? $item['product'] ?? 'N/A' }}</td>
                    <td>{{ $item['quantity']['value'] ?? $item['quantity'] ?? 1 }}</td>
                    <td>
                        <form action="{{ route('cart.remove', urlencode($item['item']['value'] ?? $item['item'])) }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-sm">Remove</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="text-end mt-4">
            <a href="{{ route('cart.checkout') }}" class="btn btn-primary">Proceed to Checkout</a>
        </div>
    @endif
</div>
@endsection
