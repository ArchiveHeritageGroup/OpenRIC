@extends('theme::layouts.1col')

@section('title', 'Validate Import')
@section('body-class', 'admin data-migration validate')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-check-double me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Validate Import</h1><span class="small text-muted">Data Migration</span></div>
  </div>

  <div class="card">
    <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Validation Results</strong></div>
    <div class="card-body">
      @if(isset($validation))
        @if($validation['valid'] ?? false)
          <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i> File is valid. {{ number_format($validation['totalRows'] ?? 0) }} rows ready for import.</div>
        @else
          <div class="alert alert-danger"><i class="fas fa-times-circle me-2"></i> Validation failed.</div>
          @if(!empty($validation['errors']))
            <ul class="list-group mb-3">@foreach($validation['errors'] as $err)<li class="list-group-item list-group-item-danger">{{ $err }}</li>@endforeach</ul>
          @endif
        @endif
        @if(!empty($validation['warnings']))
          <ul class="list-group">@foreach($validation['warnings'] as $warn)<li class="list-group-item list-group-item-warning">{{ $warn }}</li>@endforeach</ul>
        @endif
      @else
        <p class="text-muted">No validation data available.</p>
      @endif
    </div>
  </div>
@endsection
