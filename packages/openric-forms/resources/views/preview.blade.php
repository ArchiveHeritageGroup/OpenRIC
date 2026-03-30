@extends('theme::layout')

@section('title', 'Preview - ' . ($template->name ?? 'Form'))

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="{{ route('forms.index') }}">Forms</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('forms.builder', $template->id) }}">Builder</a></li>
                    <li class="breadcrumb-item active">Preview</li>
                </ol>
            </nav>
            <h4 class="mb-0"><i class="bi bi-eye me-2"></i>{{ $template->name ?? 'Form Preview' }}</h4>
        </div>
        <a href="{{ route('forms.builder', $template->id) }}" class="atom-btn-white">Back to Builder</a>
    </div>

    <div class="card">
        <div class="card-header">
            <h6 class="mb-0">Form Preview</h6>
        </div>
        <div class="card-body">
            @forelse($fields as $field)
                <div class="mb-3">
                    <label class="form-label">
                        {{ $field->label ?? 'Untitled' }}
                        @if($field->is_required)<span class="text-danger">*</span>@endif
                    </label>

                    @switch($field->field_type)
                        @case('text')
                            <input type="text" class="form-control" placeholder="{{ $field->placeholder ?? '' }}">
                            @break
                        @case('textarea')
                            <textarea class="form-control" rows="3" placeholder="{{ $field->placeholder ?? '' }}"></textarea>
                            @break
                        @case('richtext')
                            <textarea class="form-control richtext-editor" rows="5"></textarea>
                            @break
                        @case('date')
                            <input type="date" class="form-control">
                            @break
                        @case('select')
                            <select class="form-select">
                                <option value="">Select...</option>
                            </select>
                            @break
                        @case('checkbox')
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="field_{{ $field->id }}">
                                <label class="form-check-label" for="field_{{ $field->id }}">{{ $field->label ?? '' }}</label>
                            </div>
                            @break
                        @default
                            <input type="text" class="form-control">
                    @endswitch

                    @if($field->help_text)
                        <div class="form-text">{{ $field->help_text }}</div>
                    @endif
                </div>
            @empty
                <div class="text-center text-muted p-4">No fields in this template.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
