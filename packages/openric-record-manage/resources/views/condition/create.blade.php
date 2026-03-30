@extends('theme::layouts.1col')

@section('title', 'New Condition Report')

@section('content')
    <h1 class="h3">New Condition Report</h1>
    <p class="text-muted">Record: {{ $record->title ?? '[Untitled]' }}</p>

    @include('theme::partials.alerts')

    <form method="POST" action="{{ route('record.condition.store', $record->id) }}">
        @csrf

        <div class="row g-3">
            <div class="col-md-6">
                <label for="assessment_date" class="form-label">Assessment Date *</label>
                <input type="date" name="assessment_date" id="assessment_date" class="form-control" value="{{ old('assessment_date', date('Y-m-d')) }}" required>
            </div>
            <div class="col-md-6">
                <label for="overall_rating" class="form-label">Overall Rating *</label>
                <select name="overall_rating" id="overall_rating" class="form-select" required>
                    @foreach($ratingOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('overall_rating') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="context" class="form-label">Context</label>
                <select name="context" id="context" class="form-select">
                    @foreach($contextOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('context') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="priority" class="form-label">Priority</label>
                <select name="priority" id="priority" class="form-select">
                    @foreach($priorityOptions as $value => $label)
                        <option value="{{ $value }}" {{ old('priority', 'normal') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label for="summary" class="form-label">Summary</label>
                <textarea name="summary" id="summary" class="form-control" rows="3">{{ old('summary') }}</textarea>
            </div>
            <div class="col-12">
                <label for="recommendations" class="form-label">Recommendations</label>
                <textarea name="recommendations" id="recommendations" class="form-control" rows="3">{{ old('recommendations') }}</textarea>
            </div>
            <div class="col-md-6">
                <label for="next_check_date" class="form-label">Next Check Date</label>
                <input type="date" name="next_check_date" id="next_check_date" class="form-control" value="{{ old('next_check_date') }}">
            </div>
            <div class="col-12">
                <label for="environmental_notes" class="form-label">Environmental Notes</label>
                <textarea name="environmental_notes" id="environmental_notes" class="form-control" rows="2">{{ old('environmental_notes') }}</textarea>
            </div>
            <div class="col-12">
                <label for="handling_notes" class="form-label">Handling Notes</label>
                <textarea name="handling_notes" id="handling_notes" class="form-control" rows="2">{{ old('handling_notes') }}</textarea>
            </div>
            <div class="col-12">
                <label for="storage_notes" class="form-label">Storage Notes</label>
                <textarea name="storage_notes" id="storage_notes" class="form-control" rows="2">{{ old('storage_notes') }}</textarea>
            </div>
        </div>

        <div class="d-flex gap-2 mt-4">
            <a href="{{ route('record.condition.index', $record->id) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Report</button>
        </div>
    </form>
@endsection
