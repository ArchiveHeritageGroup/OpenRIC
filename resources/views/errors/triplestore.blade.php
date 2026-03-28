@extends('theme::layouts.1col')

@section('title', 'Triplestore Unavailable')

@section('content')
<div class="text-center py-5">
    <h1 class="display-4 text-warning"><i class="bi bi-exclamation-triangle"></i></h1>
    <h2 class="h4 mb-3">Triplestore Temporarily Unavailable</h2>
    <p class="text-muted">{{ $message }}</p>
    <p class="text-muted small">{{ $detail }}</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">Return to Dashboard</a>
    <button onclick="location.reload()" class="btn btn-outline-secondary mt-3">Retry</button>
</div>
@endsection
