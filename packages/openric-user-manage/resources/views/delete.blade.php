@extends('theme::layouts.1col')

@php $slug_ = is_array($user) ? ($user['slug'] ?? '') : ($user->slug ?? ''); $afon_ = is_array($user) ? ($user['authorized_form_of_name'] ?? $user['username'] ?? '') : ($user->authorized_form_of_name ?? $user->username ?? ''); @endphp

@section('title')
  <h1>Are you sure you want to delete {{ $afon_ }}?</h1>
@endsection

@section('content')

  @if(isset($noteCount) && $noteCount > 0)
    <div id="content" class="p-3">
      This user has {{ $noteCount }} note(s) in the system. These notes will not be deleted, but their association with this user will be removed.
    </div>
  @endif

  <form method="POST" action="{{ route('user.destroy', $slug_) }}">
    @csrf
    @method('DELETE')
    <ul class="nav gap-2 mb-3">
      <li><a href="{{ route('user.show', $slug_) }}" class="btn btn-outline-secondary">Cancel</a></li>
      <li><input class="btn btn-outline-danger" type="submit" value="Delete"></li>
    </ul>
  </form>
@endsection
