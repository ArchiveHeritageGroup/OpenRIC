@extends('theme::layouts.1col')

@section('title', 'DOI Sync')
@section('body-class', 'admin doi sync')

@section('content')
  <div class="card">
    <div class="card-body text-center py-5">
      <i class="fas fa-sync fa-4x text-success mb-3"></i>
      <h3>DOI Metadata Sync</h3>
      <p class="text-muted">
        The metadata synchronisation request has been submitted. If connected to
        DataCite, the latest local metadata will be pushed to the DOI record.
      </p>

      @if(session('success'))
        <div class="alert alert-success d-inline-block">
          {{ session('success') }}
        </div>
      @endif

      @if(session('error'))
        <div class="alert alert-danger d-inline-block">
          {{ session('error') }}
        </div>
      @endif

      <div class="mt-3">
        <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i> Back
        </a>
      </div>
    </div>
  </div>
@endsection
