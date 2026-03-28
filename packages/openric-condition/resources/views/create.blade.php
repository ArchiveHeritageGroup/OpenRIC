@extends('theme::layouts.1col')
@section('title', 'New Condition Assessment')
@section('content')
<h1 class="h3 mb-4">New Condition Assessment</h1>
@include('theme::partials.alerts')
<form method="POST" action="{{ route('condition.store') }}">@csrf
    <div class="card"><div class="card-body">
        <div class="mb-3"><label for="object_iri" class="form-label">Object IRI <span class="text-danger">*</span></label><input type="text" class="form-control" id="object_iri" name="object_iri" value="{{ old('object_iri', $object_iri) }}" required></div>
        <div class="row">
            <div class="col-md-6 mb-3"><label for="condition_code" class="form-label">Condition Code <span class="text-danger">*</span></label>
                <select class="form-select" id="condition_code" name="condition_code" required>
                    <option value="excellent">Excellent</option><option value="good">Good</option><option value="fair">Fair</option><option value="poor">Poor</option><option value="critical">Critical</option>
                </select></div>
            <div class="col-md-6 mb-3"><label for="condition_label" class="form-label">Condition Label <span class="text-danger">*</span></label><input type="text" class="form-control" id="condition_label" name="condition_label" value="{{ old('condition_label') }}" required></div>
        </div>
        <div class="row">
            <div class="col-md-4 mb-3"><label for="conservation_priority" class="form-label">Conservation Priority (0-5)</label><input type="number" class="form-control" id="conservation_priority" name="conservation_priority" min="0" max="5" value="0"></div>
            <div class="col-md-4 mb-3"><label for="completeness_pct" class="form-label">Completeness %</label><input type="number" class="form-control" id="completeness_pct" name="completeness_pct" min="0" max="100" value="100"></div>
            <div class="col-md-4 mb-3"><label for="next_assessment_date" class="form-label">Next Assessment</label><input type="date" class="form-control" id="next_assessment_date" name="next_assessment_date"></div>
        </div>
        <div class="mb-3"><label for="storage_requirements" class="form-label">Storage Requirements</label><textarea class="form-control" id="storage_requirements" name="storage_requirements" rows="2">{{ old('storage_requirements') }}</textarea></div>
        <div class="mb-3"><label for="recommendations" class="form-label">Recommendations</label><textarea class="form-control" id="recommendations" name="recommendations" rows="2">{{ old('recommendations') }}</textarea></div>
    </div></div>
    <div class="mt-3"><button type="submit" class="btn btn-primary">Record Assessment</button> <a href="{{ route('condition.index') }}" class="btn btn-secondary">Cancel</a></div>
</form>
@endsection
