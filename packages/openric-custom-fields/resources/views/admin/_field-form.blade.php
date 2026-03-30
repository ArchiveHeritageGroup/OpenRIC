@php
    $isEdit = !empty($field);
@endphp

<form method="POST" action="{{ $isEdit ? route('custom-fields.update', $field->id) : route('custom-fields.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    @if($errors->any())
        <div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    <div class="row">
        <div class="col-md-8">
            <div class="mb-3">
                <label for="name" class="form-label">Field Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $field->name ?? old('name', '') }}" required pattern="[a-z0-9_]+" title="Lowercase letters, numbers, and underscores only">
                <div class="form-text">Used internally. Lowercase letters, numbers, underscores only.</div>
            </div>

            <div class="mb-3">
                <label for="label" class="form-label">Display Label</label>
                <input type="text" class="form-control" id="label" name="label" value="{{ $field->label ?? old('label', '') }}">
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="entity_type" class="form-label">Entity Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="entity_type" name="entity_type" required>
                        <option value="">-- Select --</option>
                        @foreach($entityTypes as $key => $label)
                            <option value="{{ $key }}" {{ ($field->entity_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="field_type" class="form-label">Field Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="field_type" name="field_type" required>
                        <option value="">-- Select --</option>
                        @foreach($fieldTypes as $key => $label)
                            <option value="{{ $key }}" {{ ($field->field_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="options" class="form-label">Options (for dropdown)</label>
                <textarea class="form-control" id="options" name="options" rows="3" placeholder="One option per line">{{ is_array($field->options ?? null) ? implode("\n", $field->options) : ($field->options ?? old('options', '')) }}</textarea>
                <div class="form-text">One option per line. Only used for dropdown field type.</div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h6 class="mb-0">Settings</h6></div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_required" name="is_required" value="1" {{ ($field->is_required ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_required">Required</label>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ ($field->is_active ?? true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    <div class="mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="{{ $field->sort_order ?? old('sort_order', 0) }}" min="0">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr>
    <div class="d-flex justify-content-between">
        <a href="{{ route('custom-fields.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>{{ $isEdit ? 'Update Field' : 'Create Field' }}</button>
    </div>
</form>
