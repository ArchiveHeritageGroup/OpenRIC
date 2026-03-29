@extends('theme::layouts.1col')
@section('title', 'Delete Template')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <h1><i class="fas fa-trash me-2"></i>Delete Template</h1>

    <div class="card">
      <div class="card-header bg-danger text-white">Confirm Deletion</div>
      <div class="card-body">
        @if(isset($report))
          <p>Are you sure you want to delete template <strong>{{ $report->name ?? '' }}</strong>?</p>
          <form method="post" action="{{ route('reports.builder.delete-template', $report->id) }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i>Delete</button>
            <a href="{{ route('reports.builder.templates') }}" class="btn btn-outline-secondary">Cancel</a>
          </form>
        @else
          <p class="text-muted">Template not found.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
