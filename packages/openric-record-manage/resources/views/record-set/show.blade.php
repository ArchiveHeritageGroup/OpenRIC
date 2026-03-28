@extends('theme::layouts.2col')

@section('title', $entity['properties']['rico:title'][0]['value'] ?? 'Record Set')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">{{ $entity['properties']['rico:title'][0]['value'] ?? 'Record Set' }}</h1>
        <div class="btn-group">
            <a href="{{ route('record-sets.edit', ['iri' => urlencode($entity['iri'])]) }}" class="btn btn-outline-primary btn-sm">Edit</a>
            <form method="POST" action="{{ route('record-sets.destroy', ['iri' => urlencode($entity['iri'])]) }}" class="d-inline" onsubmit="return confirm('Delete this Record Set?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-outline-danger btn-sm">Delete</button>
            </form>
        </div>
    </div>

    @include('theme::partials.alerts')
    @include('theme::partials.view-switch')

    <div class="card mt-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">IRI</dt>
                <dd class="col-sm-9"><code class="small">{{ $entity['iri'] }}</code></dd>

                <dt class="col-sm-3">Type</dt>
                <dd class="col-sm-9"><span class="badge bg-primary">{{ $entity['type'] ?? 'rico:RecordSet' }}</span></dd>

                @foreach($entity['properties'] ?? [] as $predicate => $values)
                    <dt class="col-sm-3">{{ $predicate }}</dt>
                    <dd class="col-sm-9">
                        @foreach($values as $val)
                            <span>{{ $val['value'] ?? '' }}</span>@if(!$loop->last), @endif
                        @endforeach
                    </dd>
                @endforeach
            </dl>
        </div>
    </div>

    @if(count($creators) > 0)
        <h2 class="h5 mt-4">Creators</h2>
        <ul class="list-group">
            @foreach($creators as $creator)
                <li class="list-group-item">{{ $creator['name']['value'] ?? $creator['agent']['value'] ?? 'Unknown' }}</li>
            @endforeach
        </ul>
    @endif

    @if(count($children) > 0)
        <h2 class="h5 mt-4">Children</h2>
        <ul class="list-group">
            @foreach($children as $child)
                <li class="list-group-item">
                    <a href="{{ route('record-sets.show', ['iri' => urlencode($child['child']['value'] ?? '')]) }}">
                        {{ $child['title']['value'] ?? 'Untitled' }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
