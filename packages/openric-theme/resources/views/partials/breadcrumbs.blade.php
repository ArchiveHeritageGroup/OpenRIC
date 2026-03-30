@if(isset($breadcrumbs) && count($breadcrumbs) > 0)
<nav aria-label="Breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/">Home</a></li>
        @foreach($breadcrumbs as $crumb)
            @if($loop->last)
                <li class="breadcrumb-item active" aria-current="page">{{ $crumb['label'] }}</li>
            @else
                <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a></li>
            @endif
        @endforeach
    </ol>
</nav>
@endif
