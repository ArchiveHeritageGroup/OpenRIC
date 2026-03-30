@extends('theme::layouts.1col')

@section('title', 'Access Restricted')

@section('content')
  <div class="text-center py-5">
    <h1 class="mb-3">Access Restricted</h1>
    <p class="text-muted">This record is currently under embargo and cannot be accessed.</p>
    <a href="{{ url('/') }}" class="btn btn-outline-primary">Return to Home</a>
  </div>
@endsection
