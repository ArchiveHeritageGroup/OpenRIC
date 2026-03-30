@extends('theme::layouts.1col')

@section('title', 'Batch Assign Rights')

@section('content')
  <h1 class="mb-3">Batch Assign Rights</h1>

  <form method="POST" action="{{ route('rights.extended.batch.store') }}">
    @csrf
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><h5 class="mb-0">Target Entities</h5></div>
      <div class="card-body">
        <div class="mb-3">
          <label for="entity_iris_text" class="form-label">Entity IRIs (one per line) <span class="text-danger">*</span></label>
          <textarea name="entity_iris_text" id="entity_iris_text" class="form-control" rows="6" placeholder="Enter entity IRIs, one per line"></textarea>
          <small class="text-muted">Each line will receive the rights assignment below.</small>
        </div>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><h5 class="mb-0">Rights Assignment</h5></div>
      <div class="card-body">
        <div class="mb-3">
          <label for="rights_basis" class="form-label">Rights basis <span class="text-danger">*</span></label>
          <select name="rights_basis" id="rights_basis" class="form-select" required>
            <option value="copyright">Copyright</option>
            <option value="license">License</option>
            <option value="statute">Statute</option>
            <option value="other">Other</option>
          </select>
        </div>
        <div class="mb-3"><label for="terms" class="form-label">Terms</label><textarea name="terms" id="terms" class="form-control" rows="3"></textarea></div>
        <div class="mb-3"><label for="notes" class="form-label">Notes</label><textarea name="notes" id="notes" class="form-control" rows="3"></textarea></div>
      </div>
    </div>

    <div class="d-flex gap-2 mb-3">
      <a href="{{ route('rights.extended.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
      <button type="submit" class="btn btn-outline-success">Apply Batch</button>
    </div>
  </form>
@endsection
