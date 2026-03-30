@extends('theme::layouts.1col')

@section('title', 'Asset Valuations')

@section('content')
<h1>Asset Valuations</h1>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Asset #</th>
                <th>Asset Title</th>
                <th>Valuation Date</th>
                <th>Type</th>
                <th>Previous</th>
                <th>New Value</th>
                <th>Valuer</th>
            </tr>
        </thead>
        <tbody>
            @forelse($valuations as $v)
                <tr>
                    <td>{{ $v->asset_number ?? 'N/A' }}</td>
                    <td>{{ $v->asset_title ?? 'Unknown' }}</td>
                    <td>{{ $v->valuation_date }}</td>
                    <td>{{ ucfirst($v->valuation_type ?? 'unknown') }}</td>
                    <td>{{ number_format($v->previous_value ?? 0, 2) }}</td>
                    <td>{{ number_format($v->new_value ?? 0, 2) }}</td>
                    <td>{{ $v->valuer_name ?? 'Unknown' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No valuations recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
