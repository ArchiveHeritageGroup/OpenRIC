@extends('theme::layouts.1col')

@section('title', 'Create Asset')

@section('content')
<h1>Create Heritage Asset</h1>

<form method="POST">
    @csrf
    
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Asset Details</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">Select Category</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Acquisition Date</label>
                    <input type="date" name="acquisition_date" class="form-control">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Acquisition Method</label>
                    <input type="text" name="acquisition_method" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Acquisition Cost</label>
                    <input type="number" step="0.01" name="acquisition_cost" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Currency</label>
                    <input type="text" name="acquisition_currency" class="form-control" value="ZAR">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Valuation</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Valuation Basis</label>
                    <select name="valuation_basis" class="form-select">
                        <option value="">Select Basis</option>
                        <option value="cost">Cost</option>
                        <option value="market">Market Value</option>
                        <option value="insurance">Insurance Value</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Current Value</label>
                    <input type="number" step="0.01" name="current_value" class="form-control">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Condition Rating</label>
                    <select name="condition_rating" class="form-select">
                        <option value="">Select Rating</option>
                        <option value="excellent">Excellent</option>
                        <option value="good">Good</option>
                        <option value="fair">Fair</option>
                        <option value="poor">Poor</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="text-end">
        <a href="{{ route('ipsas.assets') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Create Asset</button>
    </div>
</form>
@endsection
