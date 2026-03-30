@extends('theme::layouts.1col')

@section('title', 'IPSAS Heritage Asset Management')

@section('content')
<h1>IPSAS Dashboard</h1>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Asset Summary</h5>
            </div>
            <div class="card-body">
                <p><strong>Total Assets:</strong> {{ $stats['assets']['total'] ?? 0 }}</p>
                <p><strong>Active Assets:</strong> {{ $stats['assets']['active'] ?? 0 }}</p>
                <p><strong>Total Value:</strong> {{ number_format($stats['values']['total'] ?? 0, 2) }}</p>
                <p><strong>Insured Value:</strong> {{ number_format($stats['values']['insured'] ?? 0, 2) }}</p>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Compliance Status</h5>
            </div>
            <div class="card-body">
                @if(empty($compliance['warnings']))
                    <p class="text-success"><i class="fa fa-check-circle me-2"></i>No compliance warnings</p>
                @else
                    @foreach($compliance['warnings'] as $warning)
                        <p class="text-warning"><i class="fa fa-exclamation-triangle me-2"></i>{{ $warning }}</p>
                    @endforeach
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Quick Links</h5>
            </div>
            <div class="card-body">
                <a href="{{ route('ipsas.assets') }}" class="btn btn-outline-primary me-2">View Assets</a>
                <a href="{{ route('ipsas.valuations') }}" class="btn btn-outline-primary me-2">Valuations</a>
                <a href="{{ route('ipsas.insurance') }}" class="btn btn-outline-primary me-2">Insurance</a>
                <a href="{{ route('ipsas.reports') }}" class="btn btn-outline-primary me-2">Reports</a>
                <a href="{{ route('ipsas.config') }}" class="btn btn-outline-secondary">Configuration</a>
            </div>
        </div>
    </div>
</div>
@endsection
