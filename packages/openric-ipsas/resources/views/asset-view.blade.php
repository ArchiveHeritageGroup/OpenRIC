@extends('theme::layouts.1col')

@section('title', 'Asset Details')

@section('content')
<h1>Heritage Asset: {{ $asset->title ?? 'Untitled' }}</h1>

<div class="row mb-4">
    <div class="col-md-12">
        <a href="{{ route('ipsas.assets') }}" class="btn btn-secondary">Back</a>
        <a href="{{ route('ipsas.asset.edit', $asset->id) }}" class="btn btn-primary">Edit</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Asset Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Asset Number:</strong> {{ $asset->asset_number ?? 'N/A' }}</p>
                <p><strong>Title:</strong> {{ $asset->title ?? 'Untitled' }}</p>
                <p><strong>Category:</strong> {{ $asset->category_name ?? 'Uncategorized' }}</p>
                <p><strong>Description:</strong> {{ $asset->description ?? 'No description' }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Location:</strong> {{ $asset->location ?? 'Unknown' }}</p>
                <p><strong>Status:</strong> <span class="badge bg-{{ ($asset->status ?? '') == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($asset->status ?? 'unknown') }}</span></p>
                <p><strong>Current Value:</strong> {{ number_format($asset->current_value ?? 0, 2) }}</p>
                <p><strong>Condition:</strong> {{ ucfirst($asset->condition_rating ?? 'Not rated') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">Valuations</h5>
    </div>
    <div class="card-body">
        @if($valuations->isEmpty())
            <p class="text-muted">No valuations recorded.</p>
        @else
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Previous</th>
                        <th>New</th>
                        <th>Valuer</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($valuations as $v)
                        <tr>
                            <td>{{ $v->valuation_date }}</td>
                            <td>{{ $v->valuation_type }}</td>
                            <td>{{ number_format($v->previous_value ?? 0, 2) }}</td>
                            <td>{{ number_format($v->new_value ?? 0, 2) }}</td>
                            <td>{{ $v->valuer_name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        <a href="{{ route('ipsas.valuation.create', ['asset_id' => $asset->id]) }}" class="btn btn-sm btn-success">+ Add Valuation</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header bg-warning">
        <h5 class="mb-0">Impairments</h5>
    </div>
    <div class="card-body">
        @if($impairments->isEmpty())
            <p class="text-muted">No impairments recorded.</p>
        @else
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Recognized</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($impairments as $imp)
                        <tr>
                            <td>{{ $imp->assessment_date }}</td>
                            <td>{{ $imp->description }}</td>
                            <td>{{ number_format($imp->impairment_amount ?? 0, 2) }}</td>
                            <td>{{ $imp->impairment_recognized ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection
