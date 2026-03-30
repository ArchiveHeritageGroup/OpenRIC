@extends('theme::layouts.1col')

@section('title', 'Inventory: ' . ($entity->title ?? $entity['properties']['rico:title'][0]['value'] ?? 'Record'))

@section('content')
    <h1 class="h3">Inventory</h1>
    <p class="text-muted">Children of: {{ $entity->title ?? $entity['properties']['rico:title'][0]['value'] ?? '[Untitled]' }}</p>

    @include('theme::partials.alerts')

    @if(!empty($children))
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Level</th>
                        <th>Identifier</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($children as $child)
                        <tr>
                            <td>
                                <a href="{{ route('records.show', ['iri' => urlencode($child['iri'] ?? '')]) }}">
                                    {{ $child['title'] ?? '[Untitled]' }}
                                </a>
                            </td>
                            <td>{{ $child['level'] ?? '' }}</td>
                            <td>{{ $child['identifier'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p>No child records found.</p>
    @endif
@endsection
