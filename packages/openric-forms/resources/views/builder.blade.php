@extends('theme::layout')

@section('title', 'Form Builder - ' . ($template->name ?? ''))

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('forms.index') }}">Forms</a></li>
                    <li class="breadcrumb-item active">Builder</li>
                </ol>
            </nav>
            <h4 class="mb-0"><i class="bi bi-palette2 me-2"></i>{{ $template->name ?? 'Form Builder' }}</h4>
        </div>
        <div>
            <a href="{{ route('forms.preview', $template->id) }}" class="atom-btn-white me-2">Preview</a>
            <a href="{{ route('forms.index') }}" class="atom-btn-white">Back</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Field Types</h6>
                </div>
                <div class="card-body">
                    @foreach($fieldTypes as $type => $label)
                        <div class="mb-2">
                            <button type="button" class="btn btn-outline-primary btn-sm w-100 text-start"
                                    onclick="addField('{{ $type }}')">
                                <i class="bi bi-plus me-1"></i>{{ $label }}
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Form Fields</h6>
                </div>
                <div class="card-body" id="field-list">
                    @forelse($fields as $field)
                        <div class="field-item border rounded p-3 mb-2" data-field-id="{{ $field->id }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <strong>{{ $field->label ?? 'Untitled Field' }}</strong>
                                <span class="badge bg-secondary">{{ $fieldTypes[$field->field_type] ?? $field->field_type }}</span>
                            </div>
                            <div class="small text-muted">
                                <span>Name: {{ $field->field_name }}</span>
                                @if($field->is_required)
                                    <span class="ms-2 badge bg-danger">Required</span>
                                @endif
                            </div>
                            <div class="mt-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="editField({{ $field->id }})">Edit</button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteField({{ $field->id }})">Delete</button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-muted p-4">
                            No fields yet. Click a field type on the left to add fields.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function addField(type) {
    var templateId = {{ $template->id }};
    var label = prompt('Enter field label:');
    if (!label) return;

    fetch('/forms/field/add', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({
            template_id: templateId,
            field_type: type,
            label: label
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function editField(fieldId) {
    alert('Edit field: ' + fieldId);
}

function deleteField(fieldId) {
    if (!confirm('Delete this field?')) return;

    fetch('/forms/field/delete', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ field_id: fieldId })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.querySelector('[data-field-id="' + fieldId + '"]').remove();
        }
    });
}
</script>
@endpush
@endsection
