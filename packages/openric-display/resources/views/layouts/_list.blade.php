{{-- List layout - tabular view for search results --}}
<tr class="list-item" data-id="{{ $object->id }}">
    @if($digitalObject && ($data['thumbnail_size'] ?? '') !== 'none')
    <td width="60">
        <img src="{{ $digitalObject->path }}" class="rounded" style="width: 50px; height: 50px; object-fit: cover;" alt="">
    </td>
    @endif

    @foreach($fields['identity'] as $field)
    <td>
        @if($field['code'] === 'title')
        <a href="{{ route('display.show', ['id' => $object->id]) }}">
            <strong>{{ $field['value'] }}</strong>
        </a>
        @else
        {!! format_field_value($field) !!}
        @endif
    </td>
    @endforeach

    <td class="text-end">
        @foreach($data['actions'] ?? [] as $action)
            @if($action === 'view')
            <a href="{{ route('display.show', ['id' => $object->id]) }}" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-eye"></i>
            </a>
            @endif
        @endforeach
    </td>
</tr>
