@extends('theme::layouts.1col')

@section('title', 'Edit Record')

@section('content')
    <h1 class="h3 mb-4">Edit Record</h1>

    @include('theme::partials.alerts')

    <form method="POST" action="{{ route('records.update', ['iri' => urlencode($entity['iri'])]) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $entity['properties']['rico:title'][0]['value'] ?? '') }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="identifier" class="form-label">Identifier</label>
                    <input type="text" class="form-control" id="identifier" name="identifier" value="{{ old('identifier', $entity['properties']['rico:identifier'][0]['value'] ?? '') }}">
                </div>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('records.show', ['iri' => urlencode($entity['iri'])]) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
