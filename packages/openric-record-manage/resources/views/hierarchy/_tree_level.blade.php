<ul class="list-group list-group-flush ms-{{ min($depth * 3, 9) }}">
    @foreach($nodes as $node)
        <li class="list-group-item border-0 py-1">
            <div class="d-flex align-items-center">
                @if($node['childCount'] > 0)
                    <a href="{{ route('hierarchy.tree', ['iri' => urlencode($node['iri'])]) }}" class="text-decoration-none me-1" title="Expand">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <span class="me-1" style="width:16px;display:inline-block;"></span>
                @endif

                @php
                    $typeLabel = 'Item';
                    $typeBadge = 'secondary';
                    $typeStr = $node['type'] ?? '';
                    if (str_contains($typeStr, 'RecordSet')) { $typeLabel = 'Series'; $typeBadge = 'primary'; }
                    elseif (str_contains($typeStr, 'RecordPart')) { $typeLabel = 'Part'; $typeBadge = 'warning'; }
                    elseif (str_contains($typeStr, 'Record')) { $typeLabel = 'Item'; $typeBadge = 'info'; }
                @endphp

                <span class="badge bg-{{ $typeBadge }} me-2" style="min-width:50px;">{{ $typeLabel }}</span>
                <a href="{{ route('record-sets.show', ['iri' => urlencode($node['iri'])]) }}">
                    {{ $node['title'] }}
                </a>
                @if($node['childCount'] > 0)
                    <span class="text-muted small ms-2">({{ $node['childCount'] }})</span>
                @endif
            </div>

            @if(!empty($node['children']))
                @include('record-manage::hierarchy._tree_level', ['nodes' => $node['children'], 'depth' => $depth + 1])
            @endif
        </li>
    @endforeach
</ul>
