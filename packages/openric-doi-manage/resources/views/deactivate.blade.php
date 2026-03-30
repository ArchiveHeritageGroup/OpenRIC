@extends('theme::layouts.1col')

@section('title', 'Deactivate DOI')
@section('body-class', 'admin doi deactivate')

@section('content')
  <div class="card border-danger">
    <div class="card-header bg-danger text-white fw-bold">
      <i class="fas fa-exclamation-triangle me-2"></i>Confirm DOI Deactivation
    </div>
    <div class="card-body">
      <p>
        Are you sure you want to deactivate DOI
        <strong><code>{{ $doi->doi ?? 'unknown' }}</code></strong>?
      </p>
      <p class="text-muted">
        Deactivating a DOI will set its state to "registered" at DataCite. The DOI
        will no longer resolve to your landing page but the metadata record is preserved.
        This action can be reversed by reactivating the DOI.
      </p>

      <form method="POST" action="{{ route('doi.deactivate', $doi->id ?? 0) }}">
        @csrf

        <div class="mb-3">
          <label for="reason" class="form-label">Reason for deactivation</label>
          <textarea class="form-control" id="reason" name="reason" rows="3"
                    placeholder="Optional: describe why this DOI is being deactivated"></textarea>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-ban me-1"></i> Deactivate DOI
          </button>
          <a href="{{ route('doi.view', $doi->id ?? 0) }}" class="btn btn-outline-secondary">
            <i class="fas fa-times me-1"></i> Cancel
          </a>
        </div>
      </form>
    </div>
  </div>
@endsection
