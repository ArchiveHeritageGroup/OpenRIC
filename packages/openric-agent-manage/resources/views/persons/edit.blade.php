@extends('theme::layouts.1col')
@section('title', 'Edit Person')
@section('content')
<h1 class="h3 mb-4">Edit Person</h1>
@include('theme::partials.alerts')
<form method="POST" action="{{ route('persons.update', ['iri' => urlencode($entity['iri'])]) }}">@csrf @method('PUT')
    <div class="card"><div class="card-body">
        <div class="mb-3"><label for="title" class="form-label">Title <span class="text-danger">*</span></label><input type="text" class="form-control" id="title" name="title" value="{{ old('title', $entity['properties']['rico:title'][0]['value'] ?? '') }}" required></div>
        <div class="mb-3"><label for="identifier" class="form-label">Identifier</label><input type="text" class="form-control" id="identifier" name="identifier" value="{{ old('identifier', $entity['properties']['rico:identifier'][0]['value'] ?? '') }}"></div>
    </div></div>
    <div class="mt-3"><button type="submit" class="btn btn-primary">Save</button> <a href="{{ route('persons.show', ['iri' => urlencode($entity['iri'])]) }}" class="btn btn-secondary">Cancel</a></div>
</form>
@endsection