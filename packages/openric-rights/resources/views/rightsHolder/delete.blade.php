@extends('theme::layouts.1col')

@section('title', 'Delete rights holder')

@section('content')
  <h1>Are you sure you want to delete {{ $rightsHolder->name }}?</h1>

  <form method="POST" action="{{ route('rights.holders.destroy', $rightsHolder->id) }}">
    @csrf
    @method('DELETE')
    <ul class="actions mb-3 nav gap-2">
      <li><a href="{{ route('rights.holders.show', $rightsHolder->id) }}" class="btn btn-outline-secondary">Cancel</a></li>
      <li><input class="btn btn-outline-danger" type="submit" value="Delete"></li>
    </ul>
  </form>
@endsection
