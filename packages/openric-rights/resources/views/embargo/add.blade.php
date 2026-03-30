@extends('theme::layouts.1col')

@section('title', 'Add Embargo')
@section('body-class', 'embargo add')

@section('content')
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0">Add Embargo</h1>
    @if($entityIri)<span class="small text-muted">{{ $entityIri }}</span>@endif
  </div>

  <form method="POST" action="{{ route('rights.embargo.store') }}">
    @csrf

    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><h4 class="mb-0">Embargo Details</h4></div>
      <div class="card-body">
        <div class="mb-3">
          <label for="entity_iri" class="form-label">Entity IRI <span class="text-danger">*</span></label>
          <input type="text" name="entity_iri" id="entity_iri" class="form-control" required value="{{ old('entity_iri', $entityIri) }}">
        </div>

        <div class="row">
          <div class="col-md-4 mb-3">
            <label for="embargo_start" class="form-label">Start Date <span class="text-danger">*</span></label>
            <input type="date" name="embargo_start" id="embargo_start" class="form-control" required value="{{ date('Y-m-d') }}">
          </div>
          <div class="col-md-4 mb-3">
            <label for="embargo_end" class="form-label">End Date</label>
            <input type="date" name="embargo_end" id="embargo_end" class="form-control">
            <small class="text-muted">Leave blank for perpetual embargo</small>
          </div>
          <div class="col-md-4 mb-3 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="is_perpetual" value="1" id="is_perpetual">
              <label class="form-check-label" for="is_perpetual">Perpetual (no end date)</label>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <label for="reason" class="form-label">Reason</label>
          <input type="text" name="reason" id="reason" class="form-control" placeholder="e.g., Donor restriction, Privacy concerns">
        </div>

        <div class="mb-3">
          <label for="public_message" class="form-label">Public Message</label>
          <textarea name="public_message" id="public_message" class="form-control" rows="2" placeholder="Message displayed to users"></textarea>
        </div>

        <div class="mb-3">
          <label for="notes" class="form-label">Internal Notes</label>
          <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 mb-3">
      <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-outline-danger">Create Embargo</button>
    </div>
  </form>

  <script>
  document.getElementById('is_perpetual').addEventListener('change', function() {
    document.getElementById('embargo_end').disabled = this.checked;
    if (this.checked) document.getElementById('embargo_end').value = '';
  });
  </script>
@endsection
