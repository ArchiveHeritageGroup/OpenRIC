@extends('theme::layouts.1col')
@section('title', isset($field) ? 'Edit Field: ' . ($field->label ?? $field->name) : 'Add Custom Field')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="fas fa-th-list me-2"></i>{{ isset($field) ? 'Edit Field: ' . ($field->label ?? $field->name) : 'Add Custom Field' }}</h2>
    <a href="{{ route('custom-fields.index') }}" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to List</a>
</div>

<div class="card"><div class="card-body">
    @include('openric-custom-fields::admin._field-form', ['field' => $field ?? null, 'entityTypes' => $entityTypes, 'fieldTypes' => $fieldTypes])
</div></div>
@endsection
