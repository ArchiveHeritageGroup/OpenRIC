{{-- Partial: Render custom fields in edit forms --}}
@if(isset($customFields) && count($customFields) > 0)
    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="fas fa-th-list me-2"></i>Custom Fields</h6></div>
        <div class="card-body">
            @foreach($customFields as $field)
                <div class="mb-3">
                    <label for="cf_{{ $field->name }}" class="form-label">
                        {{ $field->label ?? $field->name }}
                        @if($field->is_required ?? false)<span class="text-danger">*</span>@endif
                    </label>

                    @if($field->field_type === 'textarea')
                        <textarea class="form-control" id="cf_{{ $field->name }}" name="custom_fields[{{ $field->id }}]" rows="3" {{ ($field->is_required ?? false) ? 'required' : '' }}>{{ $field->value ?? '' }}</textarea>
                    @elseif($field->field_type === 'select')
                        <select class="form-select" id="cf_{{ $field->name }}" name="custom_fields[{{ $field->id }}]" {{ ($field->is_required ?? false) ? 'required' : '' }}>
                            <option value="">-- Select --</option>
                            @foreach(explode("\n", $field->options ?? '') as $opt)
                                <option value="{{ trim($opt) }}" {{ ($field->value ?? '') === trim($opt) ? 'selected' : '' }}>{{ trim($opt) }}</option>
                            @endforeach
                        </select>
                    @elseif($field->field_type === 'checkbox')
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cf_{{ $field->name }}" name="custom_fields[{{ $field->id }}]" value="1" {{ ($field->value ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label" for="cf_{{ $field->name }}">{{ $field->label ?? $field->name }}</label>
                        </div>
                    @elseif($field->field_type === 'date')
                        <input type="date" class="form-control" id="cf_{{ $field->name }}" name="custom_fields[{{ $field->id }}]" value="{{ $field->value ?? '' }}" {{ ($field->is_required ?? false) ? 'required' : '' }}>
                    @elseif($field->field_type === 'number')
                        <input type="number" class="form-control" id="cf_{{ $field->name }}" name="custom_fields[{{ $field->id }}]" value="{{ $field->value ?? '' }}" {{ ($field->is_required ?? false) ? 'required' : '' }}>
                    @elseif($field->field_type === 'url')
                        <input type="url" class="form-control" id="cf_{{ $field->name }}" name="custom_fields[{{ $field->id }}]" value="{{ $field->value ?? '' }}" {{ ($field->is_required ?? false) ? 'required' : '' }}>
                    @else
                        <input type="text" class="form-control" id="cf_{{ $field->name }}" name="custom_fields[{{ $field->id }}]" value="{{ $field->value ?? '' }}" {{ ($field->is_required ?? false) ? 'required' : '' }}>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
