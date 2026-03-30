{{-- ACL Tabs — adapted from Heratio _tabs.blade.php --}}
<nav>
    <ul class="nav nav-pills mb-3 d-flex gap-2">
    @foreach($groupsMenu ?? [] as $child)
        @php $childUrl = $child['url'] ?? '#'; $childLabel = $child['label'] ?? ''; $isActive = (request()->url() == $childUrl); @endphp
        <li class="nav-item"><a href="{{ $childUrl }}" class="btn btn-outline-primary {{ $isActive ? 'active' : '' }}" @if($isActive) aria-current="page" @endif>{{ $childLabel }}</a></li>
    @endforeach
    </ul>
</nav>
