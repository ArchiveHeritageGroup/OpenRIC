@extends('theme::layouts.1col')

@section('title', '500 — Server Error')

@section('content')
<div class="text-center py-5">
    <h1 class="display-1 text-muted">500</h1>
    <h2 class="h4 mb-3">Server Error</h2>
    <p class="text-muted">Something went wrong. Please try again later.</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">Return to Dashboard</a>
</div>
@endsection
