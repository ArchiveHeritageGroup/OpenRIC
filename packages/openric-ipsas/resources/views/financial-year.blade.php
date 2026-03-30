@extends('theme::layouts.1col')

@section('title', 'Financial Year Summary')

@section('content')
<h1>Financial Year Summary - {{ $year }}</h1>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Summary</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <p><strong>Total Assets:</strong> {{ $summary['total_assets'] ?? 0 }}</p>
            </div>
            <div class="col-md-3">
                <p><strong>Total Value:</strong> {{ number_format($summary['total_value'] ?? 0, 2) }}</p>
            </div>
            <div class="col-md-2">
                <p><strong>Acquisitions:</strong> {{ $summary['acquisitions'] ?? 0 }}</p>
            </div>
            <div class="col-md-2">
                <p><strong>Valuations:</strong> {{ $summary['valuations'] ?? 0 }}</p>
            </div>
            <div class="col-md-2">
                <p><strong>Impairments:</strong> {{ $summary['impairments'] ?? 0 }}</p>
            </div>
        </div>
    </div>
</div>

<div class="text-end mb-3">
    <form method="POST" action="{{ route('ipsas.post') }}" class="d-inline">
        @csrf
        <input type="hidden" name="action" value="recalculate">
        <input type="hidden" name="year" value="{{ $year }}">
        <button type="submit" class="btn btn-warning">Recalculate</button>
    </form>
</div>
@endsection
