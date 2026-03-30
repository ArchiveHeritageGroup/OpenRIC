@extends('theme::layouts.1col')

@section('title', 'IPSAS Configuration')

@section('content')
<h1>IPSAS Configuration</h1>

<form method="POST">
    @csrf
    
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">General Settings</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Organization Name</label>
                    <input type="text" name="organization_name" class="form-control" value="{{ $config['organization_name'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Accounting Standard</label>
                    <input type="text" name="accounting_standard" class="form-control" value="{{ $config['accounting_standard'] ?? 'IPSAS' }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Default Currency</label>
                    <input type="text" name="default_currency" class="form-control" value="{{ $config['default_currency'] ?? 'ZAR' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Financial Year Start (month)</label>
                    <input type="number" name="financial_year_start" class="form-control" value="{{ $config['financial_year_start'] ?? '4' }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Valuation & Depreciation</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Depreciation Policy</label>
                    <input type="text" name="depreciation_policy" class="form-control" value="{{ $config['depreciation_policy'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Valuation Frequency (years)</label>
                    <input type="number" name="valuation_frequency_years" class="form-control" value="{{ $config['valuation_frequency_years'] ?? '3' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Nominal Value Threshold</label>
                    <input type="number" step="0.01" name="nominal_value" class="form-control" value="{{ $config['nominal_value'] ?? '0' }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-warning">
            <h5 class="mb-0">Insurance & Impairment</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Insurance Review (months)</label>
                    <input type="number" name="insurance_review_months" class="form-control" value="{{ $config['insurance_review_months'] ?? '12' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Impairment Threshold (%)</label>
                    <input type="number" step="0.1" name="impairment_threshold_percent" class="form-control" value="{{ $config['impairment_threshold_percent'] ?? '20' }}">
                </div>
            </div>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary">Save Configuration</button>
    </div>
</form>
@endsection
