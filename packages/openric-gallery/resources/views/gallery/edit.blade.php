@extends('theme::layouts.1col')

@section('title', 'Gallery Cataloguing')
@section('body-class', ($isNew ? 'create' : 'edit') . ' gallery')

@section('content')
  <h1 class="mb-2">
    Gallery Cataloguing
    <span class="d-block fs-6 text-muted fw-normal">Artwork</span>
  </h1>

  @if(session('error'))
    <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger" role="alert">
      <h5><i class="fas fa-exclamation-triangle"></i> Validation Errors</h5>
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <p class="mt-2">
        <button type="submit" name="saveAnyway" value="1" form="editForm" class="btn btn-sm btn-outline-warning">Save anyway</button>
      </p>
    </div>
  @endif

  <form method="POST" id="editForm" class="gallery-cataloguing-form"
        action="{{ $isNew ? route('gallery.artwork.store') : route('gallery.artwork.update', $artwork->slug) }}">
    @csrf
    @if(!$isNew)
      @method('PUT')
    @endif

    <input type="hidden" name="template" value="{{ old('template', $artwork->template ?? 'painting') }}">
    @if(request('parent'))
      <input type="hidden" name="parent" value="{{ request('parent') }}">
    @endif
    @if(!$isNew && isset($artwork))
      <input type="hidden" name="id" value="{{ $artwork->id }}">
    @endif

    <div class="accordion" id="galleryAccordion">

      {{-- ===== 1. Object/Work (CCO Ch 2) ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingObjectWork">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseObjectWork" aria-expanded="true" aria-controls="collapseObjectWork">
            Object/Work <span class="ms-2 small opacity-75">CCO Chapter 2</span>
          </button>
        </h2>
        <div id="collapseObjectWork" class="accordion-collapse collapse show" aria-labelledby="headingObjectWork" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <p class="small text-muted border-bottom pb-2 mb-3">Information that identifies the work, including type, components, and count.</p>

            <div class="mb-3">
              <label for="work_type" class="form-label">Work type <span class="text-danger">*</span> <span class="badge bg-danger ms-1">Required</span> <span class="badge bg-primary ms-1">CCO 2.1</span></label>
              <input type="text" class="form-control" id="work_type" name="work_type" value="{{ old('work_type', $artwork->work_type ?? '') }}" placeholder="Type to search...">
              <div class="form-text text-muted small">The type or genre of artwork (CCO: Object/Work Type).</div>
            </div>

            <div class="mb-3">
              <label for="classification" class="form-label">Classification <span class="badge bg-secondary ms-1">Optional</span></label>
              <input type="text" class="form-control" id="classification" name="classification" value="{{ old('classification', $artwork->classification ?? '') }}">
            </div>

            <div class="mb-3">
              <label for="identifier" class="form-label">Identifier <span class="badge bg-danger ms-1">Required</span> <span class="badge bg-primary ms-1">CCO 2.3</span></label>
              <input type="text" class="form-control" id="identifier" name="identifier" value="{{ old('identifier', $artwork->identifier ?? '') }}">
              <div class="form-text text-muted small">Unique identifier assigned by the repository.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 2. Titles/Names (CCO Ch 3) ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingTitle">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTitle" aria-expanded="false" aria-controls="collapseTitle">
            Titles/Names <span class="ms-2 small opacity-75">CCO Chapter 3</span>
          </button>
        </h2>
        <div id="collapseTitle" class="accordion-collapse collapse" aria-labelledby="headingTitle" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <p class="small text-muted border-bottom pb-2 mb-3">Titles, names, or other identifying phrases for the work.</p>

            <div class="mb-3">
              <label for="title" class="form-label">Title <span class="text-danger">*</span> <span class="badge bg-danger ms-1">Required</span> <span class="badge bg-primary ms-1">CCO 3.1</span></label>
              <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                     value="{{ old('title', $artwork->title ?? '') }}" required>
              @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text text-muted small">The primary title of the work.</div>
            </div>

            <div class="mb-3">
              <label for="alternate_title" class="form-label">Alternate title <span class="badge bg-secondary ms-1">Optional</span> <span class="badge bg-primary ms-1">CCO 3.2</span></label>
              <textarea class="form-control" id="alternate_title" name="alternate_title" rows="2">{{ old('alternate_title', $artwork->alternate_title ?? '') }}</textarea>
              <div class="form-text text-muted small">Other titles by which the work is known.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 3. Creation (CCO Ch 4) ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingCreation">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCreation" aria-expanded="false" aria-controls="collapseCreation">
            Creation <span class="ms-2 small opacity-75">CCO Chapter 4</span>
          </button>
        </h2>
        <div id="collapseCreation" class="accordion-collapse collapse" aria-labelledby="headingCreation" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <p class="small text-muted border-bottom pb-2 mb-3">Information about who created the work, when, and where.</p>

            <div class="mb-3">
              <label for="creator_identity" class="form-label">Creator <span class="badge bg-danger ms-1">Required</span> <span class="badge bg-primary ms-1">CCO 4.1</span></label>
              <input type="text" class="form-control" id="creator_identity" name="creator_identity" value="{{ old('creator_identity', $artwork->creator_identity ?? '') }}">
              <div class="form-text text-muted small">Creator name as it should appear in displays. Format: Surname, Forename (Nationality, birth-death).</div>
            </div>

            <div class="mb-3">
              <label for="creator_role" class="form-label">Creator role <span class="badge bg-danger ms-1">Required</span> <span class="badge bg-primary ms-1">CCO 4.1.1</span></label>
              <input type="text" class="form-control" id="creator_role" name="creator_role" value="{{ old('creator_role', $artwork->creator_role ?? '') }}">
              <div class="form-text text-muted small">The role of the creator (e.g. Artist, Attributed to, Workshop of).</div>
            </div>

            <div class="mb-3">
              <label for="creation_date_display" class="form-label">Date (display) <span class="badge bg-danger ms-1">Required</span> <span class="badge bg-primary ms-1">CCO 4.2</span></label>
              <input type="text" class="form-control" id="creation_date_display" name="creation_date_display" value="{{ old('creation_date_display', $artwork->creation_date_display ?? '') }}">
              <div class="form-text text-muted small">A free-text date for display purposes (e.g. "ca. 1885", "early 20th century", "1965-1970").</div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="creation_date_earliest" class="form-label">Earliest date <span class="badge bg-warning text-dark ms-1">Recommended</span> <span class="badge bg-primary ms-1">CCO 4.2.1</span></label>
                  <input type="date" class="form-control" id="creation_date_earliest" name="creation_date_earliest" value="{{ old('creation_date_earliest', $artwork->creation_date_earliest ?? '') }}">
                  <div class="form-text text-muted small">The earliest possible creation date in ISO 8601 format.</div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="creation_date_latest" class="form-label">Latest date <span class="badge bg-warning text-dark ms-1">Recommended</span> <span class="badge bg-primary ms-1">CCO 4.2.2</span></label>
                  <input type="date" class="form-control" id="creation_date_latest" name="creation_date_latest" value="{{ old('creation_date_latest', $artwork->creation_date_latest ?? '') }}">
                  <div class="form-text text-muted small">The latest possible creation date in ISO 8601 format.</div>
                </div>
              </div>
            </div>

            <div class="mb-3">
              <label for="creation_place" class="form-label">Place of creation <span class="badge bg-warning text-dark ms-1">Recommended</span> <span class="badge bg-primary ms-1">CCO 4.3</span></label>
              <input type="text" class="form-control" id="creation_place" name="creation_place" value="{{ old('creation_place', $artwork->creation_place ?? '') }}" placeholder="Type to search places...">
              <div class="form-text text-muted small">Geographic location where the work was created.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 4. Styles/Periods (CCO Ch 5) ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingStylesPeriods">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStylesPeriods" aria-expanded="false" aria-controls="collapseStylesPeriods">
            Styles/Periods <span class="ms-2 small opacity-75">CCO Chapter 5</span>
          </button>
        </h2>
        <div id="collapseStylesPeriods" class="accordion-collapse collapse" aria-labelledby="headingStylesPeriods" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <p class="small text-muted border-bottom pb-2 mb-3">Style, period, group, school, or movement.</p>

            <div class="mb-3">
              <label for="style" class="form-label">Style <span class="badge bg-warning text-dark ms-1">Recommended</span> <span class="badge bg-primary ms-1">CCO 5.1</span></label>
              <input type="text" class="form-control" id="style" name="style" value="{{ old('style', $artwork->style ?? '') }}">
              <div class="form-text text-muted small">The visual style of the work (e.g. "Impressionism", "Art Nouveau").</div>
            </div>
            <div class="mb-3">
              <label for="period" class="form-label">Period <span class="badge bg-secondary ms-1">Optional</span> <span class="badge bg-primary ms-1">CCO 5.2</span></label>
              <input type="text" class="form-control" id="period" name="period" value="{{ old('period', $artwork->period ?? '') }}">
              <div class="form-text text-muted small">The broad cultural or chronological period (e.g. "Renaissance", "Modern").</div>
            </div>
            <div class="mb-3">
              <label for="movement" class="form-label">Movement <span class="badge bg-secondary ms-1">Optional</span></label>
              <input type="text" class="form-control" id="movement" name="movement" value="{{ old('movement', $artwork->movement ?? '') }}">
            </div>
            <div class="mb-3">
              <label for="school" class="form-label">School/Group <span class="badge bg-secondary ms-1">Optional</span> <span class="badge bg-primary ms-1">CCO 5.3</span></label>
              <input type="text" class="form-control" id="school" name="school" value="{{ old('school', $artwork->school ?? '') }}">
              <div class="form-text text-muted small">The school of art or artistic group.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 5. Measurements (CCO Ch 6) ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingMeasurements">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMeasurements" aria-expanded="false" aria-controls="collapseMeasurements">
            Measurements <span class="ms-2 small opacity-75">CCO Chapter 6</span>
          </button>
        </h2>
        <div id="collapseMeasurements" class="accordion-collapse collapse" aria-labelledby="headingMeasurements" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <p class="small text-muted border-bottom pb-2 mb-3">Physical dimensions and other measurements.</p>

            <div class="mb-3">
              <label for="measurements" class="form-label">Measurements <span class="badge bg-danger ms-1">Required</span> <span class="badge bg-primary ms-1">CCO 6.1</span></label>
              <textarea class="form-control" id="measurements" name="measurements" rows="2">{{ old('measurements', $artwork->measurements ?? '') }}</textarea>
              <div class="form-text text-muted small">Dimensions as displayed, e.g. "72.4 x 91.4 cm".</div>
            </div>
            <div class="mb-3">
              <label for="dimensions" class="form-label">Dimensions (detailed) <span class="badge bg-secondary ms-1">Optional</span></label>
              <textarea class="form-control" id="dimensions" name="dimensions" rows="2">{{ old('dimensions', $artwork->dimensions ?? '') }}</textarea>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 6. Materials/Techniques (CCO Ch 7) ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingMaterials">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMaterials" aria-expanded="false" aria-controls="collapseMaterials">
            Materials/Techniques <span class="ms-2 small opacity-75">CCO Chapter 7</span>
          </button>
        </h2>
        <div id="collapseMaterials" class="accordion-collapse collapse" aria-labelledby="headingMaterials" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <p class="small text-muted border-bottom pb-2 mb-3">Physical materials and techniques used to create the work.</p>

            <div class="mb-3">
              <label for="materials" class="form-label">Materials <span class="badge bg-warning text-dark ms-1">Recommended</span> <span class="badge bg-primary ms-1">CCO 7.1</span></label>
              <input type="text" class="form-control" id="materials" name="materials" value="{{ old('materials', $artwork->materials ?? '') }}">
              <div class="form-text text-muted small">Medium as displayed, e.g. "oil on canvas".</div>
            </div>
            <div class="mb-3">
              <label for="techniques" class="form-label">Techniques <span class="badge bg-warning text-dark ms-1">Recommended</span> <span class="badge bg-primary ms-1">CCO 7.2</span></label>
              <input type="text" class="form-control" id="techniques" name="techniques" value="{{ old('techniques', $artwork->techniques ?? '') }}">
              <div class="form-text text-muted small">The techniques or processes used (e.g. "Impasto", "Screen printing").</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 7. Subject Matter (CCO Ch 8) ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingSubject">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSubject" aria-expanded="false" aria-controls="collapseSubject">
            Subject Matter <span class="ms-2 small opacity-75">CCO Chapter 8</span>
          </button>
        </h2>
        <div id="collapseSubject" class="accordion-collapse collapse" aria-labelledby="headingSubject" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <p class="small text-muted border-bottom pb-2 mb-3">What the work represents or depicts.</p>

            <div class="mb-3">
              <label for="scope_and_content" class="form-label">Description <span class="badge bg-warning text-dark ms-1">Recommended</span> <span class="badge bg-primary ms-1">CCO 8.1</span></label>
              <textarea class="form-control" id="scope_and_content" name="scope_and_content" rows="4">{{ old('scope_and_content', $artwork->scope_and_content ?? '') }}</textarea>
              <div class="form-text text-muted small">Subject as it should appear in displays.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 8. Inscriptions (CCO Ch 9) ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingInscriptions">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInscriptions" aria-expanded="false" aria-controls="collapseInscriptions">
            Inscriptions <span class="ms-2 small opacity-75">CCO Chapter 9</span>
          </button>
        </h2>
        <div id="collapseInscriptions" class="accordion-collapse collapse" aria-labelledby="headingInscriptions" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <p class="small text-muted border-bottom pb-2 mb-3">Marks, inscriptions, and signatures on the work.</p>

            <div class="mb-3">
              <label for="inscription" class="form-label">Inscriptions <span class="badge bg-secondary ms-1">Optional</span> <span class="badge bg-primary ms-1">CCO 9.1</span></label>
              <textarea class="form-control" id="inscription" name="inscription" rows="3">{{ old('inscription', $artwork->inscription ?? '') }}</textarea>
              <div class="form-text text-muted small">Text inscriptions on the work.</div>
            </div>
            <div class="mb-3">
              <label for="mark_description" class="form-label">Marks/Labels <span class="badge bg-secondary ms-1">Optional</span> <span class="badge bg-primary ms-1">CCO 9.3</span></label>
              <textarea class="form-control" id="mark_description" name="mark_description" rows="2">{{ old('mark_description', $artwork->mark_description ?? '') }}</textarea>
              <div class="form-text text-muted small">Collector's marks, labels, stamps.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 9. Condition (CCO Ch 12) ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingCondition">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCondition" aria-expanded="false" aria-controls="collapseCondition">
            Condition <span class="ms-2 small opacity-75">CCO Chapter 12</span>
          </button>
        </h2>
        <div id="collapseCondition" class="accordion-collapse collapse" aria-labelledby="headingCondition" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <p class="small text-muted border-bottom pb-2 mb-3">Current physical condition.</p>

            <div class="mb-3">
              <label for="condition_term" class="form-label">Condition <span class="badge bg-warning text-dark ms-1">Recommended</span> <span class="badge bg-primary ms-1">CCO 12.1</span></label>
              <input type="text" class="form-control" id="condition_term" name="condition_term" value="{{ old('condition_term', $artwork->condition_term ?? '') }}">
              <div class="form-text text-muted small">Brief summary of the current condition.</div>
            </div>
            <div class="mb-3">
              <label for="condition_description" class="form-label">Condition notes <span class="badge bg-secondary ms-1">Optional</span> <span class="badge bg-primary ms-1">CCO 12.2</span></label>
              <textarea class="form-control" id="condition_description" name="condition_description" rows="3">{{ old('condition_description', $artwork->condition_description ?? '') }}</textarea>
              <div class="form-text text-muted small">Detailed notes on the condition.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 10. Provenance & Rights (CCO Ch 13-15) ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingProvenance">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseProvenance" aria-expanded="false" aria-controls="collapseProvenance">
            Provenance &amp; Rights <span class="ms-2 small opacity-75">CCO Chapters 13-15</span>
          </button>
        </h2>
        <div id="collapseProvenance" class="accordion-collapse collapse" aria-labelledby="headingProvenance" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <div class="mb-3">
              <label for="provenance" class="form-label">Provenance <span class="badge bg-secondary ms-1">Optional</span></label>
              <textarea class="form-control" id="provenance" name="provenance" rows="3">{{ old('provenance', $artwork->provenance ?? '') }}</textarea>
            </div>
            <div class="mb-3">
              <label for="current_location" class="form-label">Current location <span class="badge bg-warning text-dark ms-1">Recommended</span> <span class="badge bg-primary ms-1">CCO 13.2</span></label>
              <input type="text" class="form-control" id="current_location" name="current_location" value="{{ old('current_location', $artwork->current_location ?? '') }}">
              <div class="form-text text-muted small">Location within the repository.</div>
            </div>
            <div class="mb-3">
              <label for="repository_id" class="form-label">Repository <span class="badge bg-danger ms-1">Required</span> <span class="badge bg-primary ms-1">CCO 13.1</span></label>
              <select class="form-select" id="repository_id" name="repository_id">
                <option value="">-- Select --</option>
                @foreach($repositories ?? [] as $repo)
                  <option value="{{ $repo->id }}" @selected(old('repository_id', $artwork->repository_id ?? '') == $repo->id)>{{ $repo->name }}</option>
                @endforeach
              </select>
              <div class="form-text text-muted small">The repository holding this work.</div>
            </div>
            <div class="mb-3">
              <label for="rights_type" class="form-label">Rights type <span class="badge bg-secondary ms-1">Optional</span> <span class="badge bg-primary ms-1">CCO 15.1</span></label>
              <input type="text" class="form-control" id="rights_type" name="rights_type" value="{{ old('rights_type', $artwork->rights_type ?? '') }}">
            </div>
            <div class="mb-3">
              <label for="rights_holder" class="form-label">Rights holder <span class="badge bg-secondary ms-1">Optional</span> <span class="badge bg-primary ms-1">CCO 15.2</span></label>
              <input type="text" class="form-control" id="rights_holder" name="rights_holder" value="{{ old('rights_holder', $artwork->rights_holder ?? '') }}">
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 11. Cataloging ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="headingCataloging">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCataloging" aria-expanded="false" aria-controls="collapseCataloging">
            Cataloging <span class="ms-2 small opacity-75">Administration</span>
          </button>
        </h2>
        <div id="collapseCataloging" class="accordion-collapse collapse" aria-labelledby="headingCataloging" data-bs-parent="#galleryAccordion">
          <div class="accordion-body">
            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="cataloger_name" class="form-label">Cataloger name <span class="badge bg-secondary ms-1">Optional</span></label>
                  <input type="text" class="form-control" id="cataloger_name" name="cataloger_name" value="{{ old('cataloger_name', $artwork->cataloger_name ?? '') }}">
                </div>
              </div>
              <div class="col-md-6">
                <div class="mb-3">
                  <label for="cataloging_date" class="form-label">Cataloging date <span class="badge bg-secondary ms-1">Optional</span></label>
                  <input type="text" class="form-control" id="cataloging_date" name="cataloging_date" value="{{ old('cataloging_date', $artwork->cataloging_date ?? '') }}">
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="mb-3">
                  <label class="form-label fw-bold">Source language</label>
                  <div>{{ $sourceCulture ?? 'English' }}</div>
                </div>
                @if(!$isNew && isset($artwork->updated_at) && $artwork->updated_at)
                <div class="mb-3">
                  <label class="form-label fw-bold">Last updated</label>
                  <div>{{ \Carbon\Carbon::parse($artwork->updated_at)->format('F j, Y, g:i a') }}</div>
                </div>
                @endif
              </div>
              <div class="col-md-6">
                @if(!empty($displayStandards))
                <div class="mb-3">
                  <label for="displayStandard" class="form-label fw-bold">Display standard <span class="badge bg-secondary ms-1">Optional</span></label>
                  <select name="displayStandard" id="displayStandard" class="form-select">
                    @foreach($displayStandards as $dsId => $dsName)
                      <option value="{{ $dsId }}" @selected(old('displayStandard', $currentDisplayStandard ?? '') == $dsId)>{{ $dsName }}</option>
                    @endforeach
                  </select>
                  <small class="form-text text-muted">Select the display standard for this record</small>
                </div>
                <div class="mb-3 form-check">
                  <input type="checkbox" class="form-check-input" id="displayStandardUpdateDescendants" name="displayStandardUpdateDescendants" value="1">
                  <label class="form-check-label" for="displayStandardUpdateDescendants">Make this selection the new default for existing children</label>
                </div>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- ===== 12. Physical Location ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="heading-physical-location">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-physical-location" aria-expanded="false" aria-controls="collapse-physical-location">
            Item Physical Location <span class="ms-2 small opacity-75">Storage &amp; Access</span>
          </button>
        </h2>
        <div id="collapse-physical-location" class="accordion-collapse collapse" aria-labelledby="heading-physical-location">
          <div class="accordion-body">
            <div class="row mb-3">
              <div class="col-md-6">
                <label class="form-label">Storage container <span class="badge bg-secondary ms-1">Optional</span></label>
                <select name="item_physical_object_id" class="form-select">
                  <option value="">-- Select container --</option>
                  @foreach($physicalObjects ?? [] as $poId => $poName)
                    <option value="{{ $poId }}" @selected(old('item_physical_object_id', $itemLocation['physical_object_id'] ?? '') == $poId)>{{ $poName }}</option>
                  @endforeach
                </select>
                <small class="form-text text-muted">Link to a physical storage container</small>
              </div>
              <div class="col-md-6">
                <label class="form-label">Item barcode <span class="badge bg-secondary ms-1">Optional</span></label>
                <input type="text" name="item_barcode" class="form-control" value="{{ old('item_barcode', $itemLocation['barcode'] ?? '') }}">
              </div>
            </div>

            <h6 class="bg-dark text-white py-2 px-3 mb-3"><i class="fas fa-box me-2"></i>Location within container</h6>
            <div class="row mb-3">
              <div class="col-md-2"><label class="form-label">Box</label><input type="text" name="item_box_number" class="form-control" value="{{ old('item_box_number', $itemLocation['box_number'] ?? '') }}"></div>
              <div class="col-md-2"><label class="form-label">Folder</label><input type="text" name="item_folder_number" class="form-control" value="{{ old('item_folder_number', $itemLocation['folder_number'] ?? '') }}"></div>
              <div class="col-md-2"><label class="form-label">Shelf</label><input type="text" name="item_shelf" class="form-control" value="{{ old('item_shelf', $itemLocation['shelf'] ?? '') }}"></div>
              <div class="col-md-2"><label class="form-label">Row</label><input type="text" name="item_row" class="form-control" value="{{ old('item_row', $itemLocation['row'] ?? '') }}"></div>
              <div class="col-md-2"><label class="form-label">Position</label><input type="text" name="item_position" class="form-control" value="{{ old('item_position', $itemLocation['position'] ?? '') }}"></div>
              <div class="col-md-2"><label class="form-label">Item #</label><input type="text" name="item_item_number" class="form-control" value="{{ old('item_item_number', $itemLocation['item_number'] ?? '') }}"></div>
            </div>

            <div class="row mb-3">
              <div class="col-md-3">
                <label class="form-label">Extent value <span class="badge bg-secondary ms-1">Optional</span></label>
                <input type="number" step="0.01" name="item_extent_value" class="form-control" value="{{ old('item_extent_value', $itemLocation['extent_value'] ?? '') }}">
              </div>
              <div class="col-md-3">
                <label class="form-label">Extent unit <span class="badge bg-secondary ms-1">Optional</span></label>
                <select name="item_extent_unit" class="form-select">
                  <option value="">-- Select --</option>
                  @foreach(['items' => 'Items', 'pages' => 'Pages', 'folders' => 'Folders', 'boxes' => 'Boxes', 'cm' => 'cm', 'm' => 'metres', 'cubic_m' => 'cubic metres'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('item_extent_unit', $itemLocation['extent_unit'] ?? '') == $val)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <h6 class="bg-dark text-white py-2 px-3 mb-3"><i class="fas fa-clipboard-check me-2"></i>Condition &amp; Status</h6>
            <div class="row mb-3">
              <div class="col-md-3">
                <label class="form-label">Condition</label>
                <select name="item_condition_status" class="form-select">
                  <option value="">-- Select --</option>
                  @foreach(['excellent' => 'Excellent', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor', 'critical' => 'Critical'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('item_condition_status', $itemLocation['condition_status'] ?? '') == $val)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Access status</label>
                <select name="item_access_status" class="form-select">
                  @foreach(['available' => 'Available', 'in_use' => 'In Use', 'restricted' => 'Restricted', 'offsite' => 'Offsite', 'missing' => 'Missing'] as $val => $label)
                    <option value="{{ $val }}" @selected(old('item_access_status', $itemLocation['access_status'] ?? 'available') == $val)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Condition notes</label>
                <input type="text" name="item_condition_notes" class="form-control" value="{{ old('item_condition_notes', $itemLocation['condition_notes'] ?? '') }}">
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-12">
                <label class="form-label">Location notes <span class="badge bg-secondary ms-1">Optional</span></label>
                <textarea name="item_location_notes" class="form-control" rows="2">{{ old('item_location_notes', $itemLocation['notes'] ?? '') }}</textarea>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>{{-- end accordion --}}

    <ul class="nav gap-2 mt-3 mb-3">
      <li><input class="btn btn-outline-success" type="submit" value="Save"></li>
      <li>
        @if($isNew)
          <a href="{{ route('gallery.artwork.browse') }}" class="btn btn-outline-secondary" role="button">Cancel</a>
        @else
          <a href="{{ route('gallery.artwork.show', $artwork->slug) }}" class="btn btn-outline-secondary" role="button">Cancel</a>
        @endif
      </li>
    </ul>
  </form>

@push('js')
<script>
(function() {
  'use strict';
  document.addEventListener('DOMContentLoaded', function() {
    updateCompleteness();
    var form = document.getElementById('editForm');
    if (form) {
      form.addEventListener('input', function() { setTimeout(updateCompleteness, 100); });
      form.addEventListener('change', function() { setTimeout(updateCompleteness, 100); });
    }

    // Help toggle buttons
    document.querySelectorAll('.btn-help').forEach(function(btn) {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        var fieldName = this.getAttribute('data-field');
        var helpDiv = document.getElementById('help-' + fieldName);
        if (helpDiv) {
          helpDiv.style.display = helpDiv.style.display === 'none' ? 'block' : 'none';
        }
      });
    });
  });
  function updateCompleteness() {
    var form = document.getElementById('editForm');
    if (!form) return;
    var fields = form.querySelectorAll('input[name]:not([type="hidden"]):not([type="submit"]), select[name], textarea[name]');
    var total = fields.length, filled = 0;
    fields.forEach(function(f) { if (f.value && f.value.trim() !== '') filled++; });
    var pct = total > 0 ? Math.round((filled / total) * 100) : 0;
    document.title = 'Gallery Cataloguing (' + pct + '% complete)';
  }
})();
</script>
@endpush
@endsection
