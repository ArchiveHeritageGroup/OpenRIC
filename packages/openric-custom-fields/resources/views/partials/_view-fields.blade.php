{{-- Partial: Display custom field values on show pages --}}
@if(isset($customFields) && count($customFields) > 0)
    <div class="card mb-3">
        <div class="card-header"><h6 class="mb-0"><i class="fas fa-th-list me-2"></i>Custom Fields</h6></div>
        <div class="card-body">
            <table class="table table-borderless table-sm mb-0">
                @foreach($customFields as $field)
                    @if($field->value)
                        <tr>
                            <th style="width:200px;">{{ $field->label ?? $field->name }}</th>
                            <td>
                                @if($field->field_type === 'checkbox')
                                    {{ $field->value ? 'Yes' : 'No' }}
                                @elseif($field->field_type === 'url')
                                    <a href="{{ $field->value }}" target="_blank">{{ $field->value }}</a>
                                @else
                                    {{ $field->value }}
                                @endif
                            </td>
                        </tr>
                    @endif
                @endforeach
            </table>
        </div>
    </div>
@endif
