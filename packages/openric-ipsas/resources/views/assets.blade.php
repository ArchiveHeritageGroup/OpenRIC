@extends('theme::layouts.1col')

@section('title', 'Heritage Assets')

@section('content')
<h1>Heritage Assets</h1>

<form method="GET" class="mb-4">
    <div class="row">
        <div class="col-md-3">
            <input type="text" name="q" class="form-control" placeholder="Search..." value="{{ $filters['search'] ?? '' }}">
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All Status</option>
                <option value="active" {{ ($filters['status'] ?? '') == 'active' ? 'selected' : '' }}>Active</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
        <div class="col-md-5 text-end">
            <a href="{{ route('ipsas.asset.create') }}" class="btn btn-success">+ Add Asset</a>
        </div>
    </div>
</form>

<div class="table-responsive">
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Asset #</th>
                <th>Title</th>
                <th>Category</th>
                <th>Current Value</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($assets as $asset)
                <tr>
                    <td>{{ $asset->asset_number ?? 'N/A' }}</td>
                    <td>{{ $asset->title ?? 'Untitled' }}</td>
                    <td>{{ $asset->category_name ?? 'Uncategorized' }}</td>
                    <td>{{ number_format($asset->current_value ?? 0, 2) }}</td>
                    <td><span class="badge bg-{{ ($asset->status ?? '') == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($asset->status ?? 'unknown') }}</span></td>
                    <td>
                        <a href="{{ route('ipsas.asset.view', $asset->id) }}" class="btn btn-sm btn-primary">View</a>
                        <a href="{{ route('ipsas.asset.edit', $asset->id) }}" class="btn btn-sm btn-secondary">Edit</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No assets found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
