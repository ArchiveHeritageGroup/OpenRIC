@extends('openric-theme::layouts.admin')

@section('title', 'Manage Orders')

@section('content')
<div class="container mt-4">
    <h2><i class="fas fa-shopping-cart me-2"></i>Manage Orders</h2>
    
    <div class="card mt-4">
        <div class="card-body">
            <p>Order management functionality would list all orders from the triplestore.</p>
            <pre>SELECT ?order ?status ?created WHERE { ?order a rico:Order ... }</pre>
        </div>
    </div>
</div>
@endsection
