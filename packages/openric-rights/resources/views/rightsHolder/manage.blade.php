@extends('theme::layouts.1col')

@section('title', 'Manage rights inheritance')

@section('content')
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0">Manage rights inheritance</h1>
    <span class="small text-muted">{{ $entityIri ?? '' }}</span>
  </div>

  @if($errors->any())
    <div class="alert alert-danger">
      @foreach($errors->all() as $error) <p>{{ $error }}</p> @endforeach
    </div>
  @endif

  <form method="POST" action="{{ route('rights.extended.batch.store') }}">
    @csrf
    <input type="hidden" name="entity_iris[]" value="{{ $entityIri ?? '' }}">

    <div class="accordion mb-3">
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#inheritance-collapse">Inheritance options</button></h2>
        <div id="inheritance-collapse" class="accordion-collapse collapse show">
          <div class="accordion-body">
            <div class="mb-3">
              <label for="rights_basis" class="form-label">Rights basis <span class="text-danger">*</span></label>
              <select name="rights_basis" id="rights_basis" class="form-select" required>
                <option value="copyright">Copyright</option>
                <option value="license">License</option>
                <option value="statute">Statute</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="terms" class="form-label">Terms</label>
              <textarea name="terms" id="terms" class="form-control" rows="4"></textarea>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ul class="actions mb-3 nav gap-2">
      <li><a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a></li>
      <li><input class="btn btn-outline-success" type="submit" value="Apply"></li>
    </ul>
  </form>
@endsection
