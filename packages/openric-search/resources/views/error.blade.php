@extends('theme::layouts.1col')

@section('title', 'Search error')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-exclamation-triangle text-danger me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">Search error encountered</h1>
    </div>
  </div>

  <div class="alert alert-danger">
    <strong>{{ $reason ?? 'An error occurred during search.' }}</strong>
    @if(!empty($error))
      <pre class="mt-2 mb-0">{{ $error }}</pre>
    @endif
  </div>

  <p><a href="javascript:history.go(-1)" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Back to previous page</a></p>
@endsection
