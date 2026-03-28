@extends('theme::layouts.2col')
@section('title', $entity['properties']['rico:title'][0]['value'] ?? 'CorporateBody')
@section('sidebar') @include('theme::partials.sidebar') @endsection
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">{{ $entity['properties']['rico:title'][0]['value'] ?? 'CorporateBody' }}</h1>
    <div class="btn-group">
        <a href="{{ route('corporate-bodies.edit', ['iri' => urlencode($entity['iri'])]) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        <form method="POST" action="{{ route('corporate-bodies.destroy', ['iri' => urlencode($entity['iri'])]) }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf @method('DELETE')<button type="submit" class="btn btn-outline-danger btn-sm">Delete</button></form>
    </div>
</div>
@include('theme::partials.alerts')
@include('theme::partials.view-switch')
<div class="card mt-3"><div class="card-body">
    <dl class="row mb-0">
        <dt class="col-sm-3">IRI</dt><dd class="col-sm-9"><code class="small">{{ $entity['iri'] }}</code></dd>
        @foreach($entity['properties'] ?? [] as $predicate => $values)
            <dt class="col-sm-3">{{ $predicate }}</dt>
            <dd class="col-sm-9">@foreach($values as $val){{ $val['value'] ?? '' }}@if(!$loop->last), @endif @endforeach</dd>
        @endforeach
    </dl>
</div></div>
@endsection