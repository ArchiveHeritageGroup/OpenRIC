@extends('theme::layouts.1col')

@section('title', 'Embargo Status')

@section('content')
  <h1 class="mb-3">Embargo Status</h1>
  <p class="text-muted">Entity: <code>{{ $entityIri ?: 'none specified' }}</code></p>

  @if($embargo)
    <div class="alert alert-danger">
      <strong>This entity is currently embargoed.</strong>
      <ul class="mb-0 mt-2">
        <li>Status: {{ ucfirst($embargo->status) }}</li>
        <li>Start: {{ $embargo->embargo_start }}</li>
        <li>End: {{ $embargo->embargo_end ?? 'Perpetual' }}</li>
        @if($embargo->reason)<li>Reason: {{ $embargo->reason }}</li>@endif
      </ul>
    </div>
  @else
    <div class="alert alert-success">This entity is not currently under embargo.</div>
  @endif
@endsection
