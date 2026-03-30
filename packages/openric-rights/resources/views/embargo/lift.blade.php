@extends('theme::layouts.1col')

@section('title', 'Lift Embargo')

@section('content')
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0">Lift Embargo</h1>
    <span class="small text-muted">{{ $embargo->entity_iri ?? '' }}</span>
  </div>

  <div class="alert alert-info mb-4">Lifting this embargo will immediately restore access to the record.</div>

  <form method="POST" action="{{ route('rights.embargo.lift', $embargo->id) }}">
    @csrf
    <div class="card mb-4">
      <div class="card-header bg-success text-white"><h4 class="mb-0">Confirm Lift Embargo</h4></div>
      <div class="card-body">
        <div class="row mb-3"><div class="col-md-4"><strong>Start Date:</strong></div><div class="col-md-8">{{ $embargo->embargo_start }}</div></div>
        @if($embargo->embargo_end)
          <div class="row mb-3"><div class="col-md-4"><strong>End Date:</strong></div><div class="col-md-8">{{ $embargo->embargo_end }}</div></div>
        @endif
        <hr>
        <div class="mb-3">
          <label for="lift_reason" class="form-label">Reason for lifting (optional)</label>
          <textarea name="lift_reason" id="lift_reason" class="form-control" rows="3" placeholder="e.g., Embargo period completed, Permission granted"></textarea>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mb-3">
      <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-outline-success">Lift Embargo</button>
    </div>
  </form>
@endsection
