@extends('theme::layouts.1col')

@section('title', 'Clear Extended Rights')

@section('content')
  <h1 class="mb-3">Clear Extended Rights</h1>
  <p class="text-muted">Entity: <code>{{ $entityIri ?: 'none' }}</code></p>

  @if(!empty($currentRights))
    <div class="alert alert-warning">This entity has {{ count($currentRights) }} rights statement(s) that will be removed.</div>
  @endif

  <form method="POST" action="{{ route('rights.extended.clear.store') }}">
    @csrf
    <input type="hidden" name="entity_iri" value="{{ $entityIri }}">
    <div class="d-flex gap-2 mb-3">
      <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-outline-danger">Clear Rights</button>
    </div>
  </form>
@endsection
