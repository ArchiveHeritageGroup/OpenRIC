@extends('theme::layouts.1col')

@section('title', 'Insurance Policies')

@section('content')
<h1>Insurance Policies</h1>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Asset #</th>
                <th>Asset Title</th>
                <th>Policy Number</th>
                <th>Sum Insured</th>
                <th>Coverage Period</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($policies as $policy)
                <tr>
                    <td>{{ $policy->asset_number ?? 'N/A' }}</td>
                    <td>{{ $policy->asset_title ?? 'Unknown' }}</td>
                    <td>{{ $policy->policy_number ?? 'N/A' }}</td>
                    <td>{{ number_format($policy->sum_insured ?? 0, 2) }}</td>
                    <td>{{ $policy->coverage_start ?? 'N/A' }} - {{ $policy->coverage_end ?? 'N/A' }}</td>
                    <td>
                        <span class="badge bg-{{ ($policy->status ?? '') == 'active' ? 'success' : 'secondary' }}">
                            {{ ucfirst($policy->status ?? 'unknown') }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No insurance policies found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
