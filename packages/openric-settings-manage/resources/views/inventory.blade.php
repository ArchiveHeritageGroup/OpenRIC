@extends('theme::layouts.1col')

@section('title', 'Inventory Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-list-ol me-2"></i>Inventory Settings</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.inventory') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <p class="text-muted mb-3">Select which levels of description should be included in inventory reports.</p>
                @php
                    $levels = $settings['levels'] ?? [
                        'fonds' => 'Fonds',
                        'subfonds' => 'Sub-fonds',
                        'collection' => 'Collection',
                        'series' => 'Series',
                        'subseries' => 'Sub-series',
                        'file' => 'File',
                        'item' => 'Item',
                        'piece' => 'Piece',
                    ];
                    $selected = $settings['selected_levels'] ?? [];
                @endphp
                @foreach ($levels as $key => $label)
                <div class="form-check mb-2">
                    <input type="hidden" name="levels[{{ $key }}]" value="0">
                    <input class="form-check-input" type="checkbox" name="levels[{{ $key }}]" id="level_{{ $key }}" value="1" {{ in_array($key, $selected) ? 'checked' : '' }}>
                    <label class="form-check-label" for="level_{{ $key }}">{{ $label }}</label>
                </div>
                @endforeach
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
