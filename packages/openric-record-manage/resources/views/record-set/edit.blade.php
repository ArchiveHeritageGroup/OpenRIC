@extends('theme::layouts.1col')

@section('title', 'Edit Record Set')

@section('content')
    <h1 class="h3 mb-4">Edit Record Set</h1>

    @include('theme::partials.alerts')

    <form method="POST" action="{{ route('record-sets.update', ['iri' => urlencode($entity['iri'])]) }}">
        @csrf
        @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $entity['properties']['rico:title'][0]['value'] ?? '') }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="identifier" class="form-label">Identifier</label>
                        <input type="text" class="form-control" id="identifier" name="identifier" value="{{ old('identifier', $entity['properties']['rico:identifier'][0]['value'] ?? '') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="level" class="form-label">Level <span class="text-danger">*</span></label>
                        <select class="form-select" id="level" name="level" required>
                            @foreach(['fonds', 'subfonds', 'series', 'subseries', 'file', 'subfile'] as $lvl)
                                <option value="{{ $lvl }}">{{ ucfirst($lvl) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="scope_and_content" class="form-label">Scope and Content</label>
                    <textarea class="form-control" id="scope_and_content" name="scope_and_content" rows="4">{{ old('scope_and_content', $entity['properties']['rico:scopeAndContent'][0]['value'] ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Save Changes</button>
            <a href="{{ route('record-sets.show', ['iri' => urlencode($entity['iri'])]) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
