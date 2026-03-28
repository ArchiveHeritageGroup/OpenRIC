@extends('theme::layouts.1col')

@section('title', 'CSV Validator Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-file-csv me-2"></i>CSV Validator</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <form method="POST" action="{{ route('settings.csv-validator') }}">
        @csrf
        <div class="card">
            <div class="card-body">
                <div class="form-check form-switch">
                    <input type="hidden" name="settings[csv_validator_enabled]" value="0">
                    <input class="form-check-input" type="checkbox" name="settings[csv_validator_enabled]" id="csv_validator_enabled" value="1" {{ ($settings['csv_validator_enabled'] ?? '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="csv_validator_enabled">Enable CSV Validation on Import</label>
                    <div class="form-text">When enabled, CSV files will be validated against the expected schema before import.</div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            </div>
        </div>
    </form>
</div>
@endsection
