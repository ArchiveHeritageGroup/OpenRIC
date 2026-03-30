@extends('openric-theme::layouts.1col')

@section('title', 'Checkout')

@section('content')
<div class="container mt-4">
    <h2><i class="fas fa-credit-card me-2"></i>Checkout</h2>
    
    <form action="{{ route('cart.payment') }}" method="POST" class="mt-4">
        @csrf
        <div class="card">
            <div class="card-body">
                <h5>Payment Method</h5>
                <div class="form-group mt-3">
                    <select name="payment_method" class="form-select" required>
                        <option value="">Select payment method...</option>
                        <option value="credit_card">Credit Card</option>
                        <option value="paypal">PayPal</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="purchase_order">Purchase Order</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="text-end mt-4">
            <a href="{{ route('cart.index') }}" class="btn btn-secondary">Back to Cart</a>
            <button type="submit" class="btn btn-primary">Complete Order</button>
        </div>
    </form>
</div>
@endsection
