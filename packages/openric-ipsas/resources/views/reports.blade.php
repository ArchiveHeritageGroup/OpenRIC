@extends('theme::layouts.1col')

@section('title', 'IPSAS Reports')

@section('content')
<h1>IPSAS Reports</h1>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Available Reports</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6>Asset Register</h6>
                        <p class="text-muted">Complete list of all heritage assets</p>
                        <a href="{{ route('ipsas.reports', ['report' => 'asset_register', 'year' => date('Y')]) }}" class="btn btn-sm btn-primary">Download CSV</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6>Valuation Summary</h6>
                        <p class="text-muted">Summary of asset valuations</p>
                        <a href="{{ route('ipsas.reports', ['report' => 'valuation_summary', 'year' => date('Y')]) }}" class="btn btn-sm btn-primary">Download CSV</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h6>Insurance Report</h6>
                        <p class="text-muted">Insurance coverage summary</p>
                        <a href="{{ route('ipsas.reports', ['report' => 'insurance', 'year' => date('Y')]) }}" class="btn btn-sm btn-primary">Download CSV</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
