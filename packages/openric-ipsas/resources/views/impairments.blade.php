@extends('theme::layouts.1col')

@section('title', 'Impairments')

@section('content')
<h1>Asset Impairments</h1>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Asset #</th>
                <th>Asset Title</th>
                <th>Assessment Date</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Recognized</th>
            </tr>
        </thead>
        <tbody>
            @forelse($impairments as $imp)
                <tr>
                    <td>{{ $imp->asset_number ?? 'N/A' }}</td>
                    <td>{{ $imp->asset_title ?? 'Unknown' }}</td>
                    <td>{{ $imp->assessment_date }}</td>
                    <td>{{ $imp->description ?? 'No description' }}</td>
                    <td>{{ number_format($imp->impairment_amount ?? 0, 2) }}</td>
                    <td>
                        @if($imp->impairment_recognized)
                            <span class="badge bg-success">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No impairments recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
