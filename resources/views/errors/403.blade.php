@extends('theme::layouts.1col')

@section('title', '403 — Forbidden')

@section('content')
<div class="text-center py-5">
    <h1 class="display-1 text-muted">403</h1>
    <h2 class="h4 mb-3">Forbidden</h2>
    <p class="text-muted">You do not have permission to access this page.</p>
    <a href="{{ url('/') }}" class="btn btn-primary mt-3">Return to Dashboard</a>
</div>
@endsection
