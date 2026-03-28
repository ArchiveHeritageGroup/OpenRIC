@extends('theme::layouts.2col')

@section('title', 'Search')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <h1 class="h3 mb-4">Search</h1>

    <form method="GET" action="{{ route('search') }}" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control form-control-lg" name="q" value="{{ $query }}" placeholder="Search archival descriptions..." aria-label="Search query" autofocus>
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    @if($query)
        <p class="text-muted">{{ $total }} result(s) for "<strong>{{ $query }}</strong>"</p>

        @forelse($items as $item)
            <div class="card mb-2">
                <div class="card-body py-2">
                    <h5 class="card-title mb-1">
                        <a href="{{ route('record-sets.show', ['iri' => urlencode($item['iri'] ?? $item['iri']['value'] ?? '')]) }}">
                            {{ $item['title'] ?? $item['title']['value'] ?? 'Untitled' }}
                        </a>
                    </h5>
                    <p class="card-text small text-muted mb-0">
                        <span class="badge bg-secondary">{{ $item['entity_type'] ?? $item['type']['value'] ?? '' }}</span>
                        {{ \Illuminate\Support\Str::limit($item['scope_and_content'] ?? '', 200) }}
                    </p>
                </div>
            </div>
        @empty
            <p class="text-muted">No results found.</p>
        @endforelse
    @endif
@endsection
