@extends('theme::layouts.1col')

@section('title', '404 — Not Found')

@section('content')
<div class="text-center py-5">
    <h1 class="display-1 text-muted">404</h1>
    <h2 class="h4 mb-3">Not Found</h2>
    <p class="text-muted">The page you are looking for does not exist.</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">Return to Dashboard</a>
</div>
@endsection
