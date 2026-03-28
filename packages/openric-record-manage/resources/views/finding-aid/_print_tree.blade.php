<ul style="list-style: none; padding-left: {{ $depth > 0 ? '1.5rem' : '0' }};">
    @foreach($nodes as $node)
        <li class="mb-2">
            <strong>{{ $node['title'] }}</strong>
            @if(!empty($node['type']))
                <span class="text-muted small">[{{ str_replace('https://www.ica.org/standards/RiC/ontology#', '', $node['type']) }}]</span>
            @endif
            @if(!empty($node['children']))
                @include('record-manage::finding-aid._print_tree', ['nodes' => $node['children'], 'depth' => $depth + 1])
            @endif
        </li>
    @endforeach
</ul>
