@extends('theme::layouts.1col')

@section('title', 'Create Valuation')

@section('content')
<h1>Record Asset Valuation</h1>

<form method="POST">
    @csrf
    
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Valuation Details</h5>
        </div>
        <div class="card-body">
            @if($asset)
            <div class="row mb-3">
                <div class="col-md-12">
                    <p><strong>Asset:</strong> {{ $asset->asset_number ?? '' }} - {{ $asset->title ?? '' }}</p>
                    <input type="hidden" name="asset_id" value="{{ $asset->id }}">
                </div>
            </div>
            @else
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Select Asset</label>
                    <input type="number" name="asset_id" class="form-control" required>
                </div>
            </div>
            @endif
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Valuation Date</label>
                    <input type="date" name="valuation_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Valuation Type</label>
                    <select name="valuation_type" class="form-select" required>
                        <option value="initial">Initial</option>
                        <option value="periodic">Periodic</option>
                        <option value="ad_hoc">Ad-hoc</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Valuation Basis</label>
                    <select name="valuation_basis" class="form-select">
                        <option value="cost">Cost</option>
                        <option value="market">Market Value</option>
                        <option value="insurance">Insurance Value</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Previous Value</label>
                    <input type="number" step="0.01" name="previous_value" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label">New Value</label>
                    <input type="number" step="0.01" name="new_value" class="form-control" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Valuer Name</label>
                    <input type="text" name="valuer_name" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Valuer Qualification</label>
                    <input type="text" name="valuer_qualification" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Valuer Type</label>
                    <select name="valuer_type" class="form-select">
                        <option value="internal">Internal</option>
                        <option value="external">External</option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Valuation Method</label>
                    <input type="text" name="valuation_method" class="form-control">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="text-end">
        <a href="{{ $asset ? route('ipsas.asset.view', $asset->id) : route('ipsas.valuations') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Record Valuation</button>
    </div>
</form>
@endsection
