@extends('theme::layouts.1col')

@section('title', 'Delete: ' . ($entity['properties']['rico:title'][0]['value'] ?? 'Record'))

@section('content')
    <h1 class="h3">Are you sure you want to delete {{ $entity['properties']['rico:title'][0]['value'] ?? '[Untitled]' }}?</h1>

    <form method="POST" action="{{ route('records.destroy', ['iri' => urlencode($entity['iri'])]) }}">
        @csrf
        @method('DELETE')

        @if(isset($childCount) && $childCount > 0)
            <div class="alert alert-warning mt-3">
                This record has {{ $childCount }} child record(s) that will also be deleted.
            </div>
        @endif

        <div class="d-flex gap-2 mt-4">
            <a href="{{ route('records.show', ['iri' => urlencode($entity['iri'])]) }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-danger">Delete</button>
        </div>
    </form>
@endsection
