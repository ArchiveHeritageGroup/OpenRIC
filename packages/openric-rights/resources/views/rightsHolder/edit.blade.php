@extends('theme::layouts.1col')

@section('title', ($rightsHolder ? 'Edit rights holder' : 'Add new rights holder'))

@section('content')
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0">{{ $rightsHolder ? 'Edit rights holder' : 'Add new rights holder' }}</h1>
    @if($rightsHolder)
      <span class="small text-muted">{{ $rightsHolder->name }}</span>
    @endif
  </div>

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="POST" action="{{ $rightsHolder ? route('rights.holders.update', $rightsHolder->id) : route('rights.holders.store') }}">
    @csrf
    @if($rightsHolder) @method('PUT') @endif

    <div class="accordion mb-3">
      {{-- Identity area --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="identity-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#identity-collapse">Identity area</button>
        </h2>
        <div id="identity-collapse" class="accordion-collapse collapse show">
          <div class="accordion-body">
            <div class="mb-3">
              <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
              <input type="text" name="name" id="name" class="form-control" required
                     value="{{ old('name', $rightsHolder->name ?? '') }}">
            </div>
            <div class="mb-3">
              <label for="description_identifier" class="form-label">Description identifier</label>
              <input type="text" name="description_identifier" id="description_identifier" class="form-control"
                     value="{{ old('description_identifier', $rightsHolder->description_identifier ?? '') }}">
            </div>
            <div class="mb-3">
              <label for="entity_iri" class="form-label">Entity IRI</label>
              <input type="text" name="entity_iri" id="entity_iri" class="form-control"
                     value="{{ old('entity_iri', $rightsHolder->entity_iri ?? '') }}">
            </div>
          </div>
        </div>
      </div>

      {{-- Description area --}}
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#desc-collapse">Description area</button></h2>
        <div id="desc-collapse" class="accordion-collapse collapse">
          <div class="accordion-body">
            @foreach(['history' => 'History', 'places' => 'Places', 'legal_status' => 'Legal status', 'functions' => 'Functions', 'mandates' => 'Mandates', 'internal_structures' => 'Internal structures', 'general_context' => 'General context'] as $field => $label)
              <div class="mb-3">
                <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                <textarea name="{{ $field }}" id="{{ $field }}" class="form-control" rows="4">{{ old($field, $rightsHolder->$field ?? '') }}</textarea>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Control area --}}
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#control-collapse">Control area</button></h2>
        <div id="control-collapse" class="accordion-collapse collapse">
          <div class="accordion-body">
            @foreach(['rules' => 'Rules and conventions', 'sources' => 'Sources', 'notes' => 'Notes'] as $field => $label)
              <div class="mb-3">
                <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                <textarea name="{{ $field }}" id="{{ $field }}" class="form-control" rows="4">{{ old($field, $rightsHolder->$field ?? '') }}</textarea>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      {{-- Contact area --}}
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#contact-collapse">Contact area</button></h2>
        <div id="contact-collapse" class="accordion-collapse collapse">
          <div class="accordion-body">
            @include('rights::rightsHolder._contact-edit', ['contacts' => $contacts])
          </div>
        </div>
      </div>
    </div>

    <ul class="actions mb-3 nav gap-2">
      @if($rightsHolder)
        <li><a href="{{ route('rights.holders.show', $rightsHolder->id) }}" class="btn btn-outline-secondary">Cancel</a></li>
        <li><input class="btn btn-outline-success" type="submit" value="Save"></li>
      @else
        <li><a href="{{ route('rights.holders.browse') }}" class="btn btn-outline-secondary">Cancel</a></li>
        <li><input class="btn btn-outline-success" type="submit" value="Create"></li>
      @endif
    </ul>
  </form>
@endsection
