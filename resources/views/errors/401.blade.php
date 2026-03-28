@extends('theme::layouts.1col')

@section('title', '401 — Unauthorized')

@section('content')
<div class="text-center py-5">
    <h1 class="display-1 text-muted">401</h1>
    <h2 class="h4 mb-3">Unauthorized</h2>
    <p class="text-muted">You need to log in to access this page.</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">Return to Dashboard</a>
</div>
@endsection
