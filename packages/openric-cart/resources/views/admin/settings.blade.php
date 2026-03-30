@extends('openric-theme::layouts.admin')

@section('title', 'Cart Settings')

@section('content')
<div class="container mt-4">
    <h2><i class="fas fa-cog me-2"></i>Cart Settings</h2>
    
    <div class="card mt-4">
        <div class="card-body">
            <h5>Payment Settings</h5>
            <p class="text-muted">Configure payment gateways and options.</p>
            
            <form>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Currency</label>
                            <select class="form-select">
                                <option value="USD">USD - US Dollar</option>
                                <option value="EUR">EUR - Euro</option>
                                <option value="GBP">GBP - British Pound</option>
                                <option value="ZAR">ZAR - South African Rand</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label>Tax Rate (%)</label>
                            <input type="number" class="form-control" value="15">
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
