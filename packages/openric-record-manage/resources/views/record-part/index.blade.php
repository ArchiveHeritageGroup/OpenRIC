@extends('theme::layouts.2col')

@section('title', 'Record Parts')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Record Parts</h1>
        <a href="{{ route('record-parts.create') }}" class="btn btn-primary">Create Record Part</a>
    </div>

    @include('theme::partials.alerts')

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead>
                <tr>
                    <th scope="col">Title</th>
                    <th scope="col">Identifier</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>
                            <a href="{{ route('record-parts.show', ['iri' => urlencode($item['iri']['value'] ?? '')]) }}">
                                {{ $item['title']['value'] ?? 'Untitled' }}
                            </a>
                        </td>
                        <td>{{ $item['identifier']['value'] ?? '-' }}</td>
                        <td>
                            <a href="{{ route('record-parts.edit', ['iri' => urlencode($item['iri']['value'] ?? '')]) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-muted text-center">No record sets found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($total > $limit)
        <nav aria-label="Record Parts pagination">
            <ul class="pagination">
                @if($offset > 0)
                    <li class="page-item"><a class="page-link" href="?offset={{ max(0, $offset - $limit) }}">Previous</a></li>
                @endif
                @if($offset + $limit < $total)
                    <li class="page-item"><a class="page-link" href="?offset={{ $offset + $limit }}">Next</a></li>
                @endif
            </ul>
        </nav>
    @endif
@endsection
