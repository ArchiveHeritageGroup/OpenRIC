@extends('theme::layouts.1col')

@section('title', 'Create Family — ISAAR-CPF')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Create Family <span class="badge bg-secondary">ISAAR-CPF</span></h1>
    @include('theme::partials.view-switch')
  </div>

  @include('theme::partials.alerts')

  <form method="POST" action="{{ route('families.store') }}" id="isaarForm">
    @csrf
    <input type="hidden" name="_form_type" value="isaar_cpf">
    <input type="hidden" name="entity_type" value="family">

    <div class="accordion mb-3" id="isaarAccordion">

      {{-- ===== 5.1 Identity Area ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="identity-heading">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#identity-collapse" aria-expanded="true" aria-controls="identity-collapse">
            5.1 Identity Area
          </button>
        </h2>
        <div id="identity-collapse" class="accordion-collapse collapse show" aria-labelledby="identity-heading" data-bs-parent="#isaarAccordion">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="entity_type_id" class="form-label">
                5.1.1 Type of entity
                <span class="text-danger">*</span> <span class="badge bg-danger ms-1">Required</span>
              </label>
              <select name="entity_type_id" id="entity_type_id" class="form-select @error('entity_type_id') is-invalid @enderror" required>
                <option value="">-- Select --</option>
                <option value="corporate_body" {{ old('entity_type_id') === 'corporate_body' ? 'selected' : '' }}>Corporate body</option>
                <option value="family" {{ old('entity_type_id', 'family') === 'family' ? 'selected' : '' }}>Family</option>
                <option value="person" {{ old('entity_type_id') === 'person' ? 'selected' : '' }}>Person</option>
              </select>
              @error('entity_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text text-muted small">"Specify the type of entity that is being described in this authority record." (ISAAR 5.1.1) Select Corporate body, Family or Person from the drop-down menu.</div>
            </div>

            <div class="mb-3">
              <label for="title" class="form-label">
                5.1.2 Authorized form of name
                <span class="text-danger">*</span> <span class="badge bg-danger ms-1">Required</span>
              </label>
              <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" required
                     value="{{ old('title') }}">
              @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text text-muted small">"Record the standardized form of name for the entity being described in accordance with any relevant national or international conventions or rules applied by the agency that created the authority record. Use dates, place, jurisdiction, occupation, epithet and other qualifiers as appropriate to distinguish the authorized form of name from those of other entities with similar names." (ISAAR 5.1.2)</div>
            </div>

            {{-- 5.1.3 Parallel form(s) of name (repeatable) --}}
            <div class="mb-3">
              <label class="form-label">5.1.3 Parallel form(s) of name <span class="badge bg-secondary ms-1">Optional</span></label>
              <div id="parallel-names-container">
                <div class="input-group mb-2 repeatable-name-row">
                  <input type="text" name="parallel_names[0]" class="form-control" value="{{ old('parallel_names.0') }}">
                  <button type="button" class="btn btn-outline-secondary remove-name-row" aria-label="Remove"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary add-name-row" data-container="parallel-names-container" data-prefix="parallel_names">
                <i class="fas fa-plus me-1" aria-hidden="true"></i>Add new
              </button>
              <div class="form-text text-muted small">"Record the parallel form(s) of name in accordance with any relevant national or international conventions or rules applied by the agency that created the authority record, including any necessary sub elements and/or qualifiers required by those conventions or rules." (ISAAR 5.1.3)</div>
            </div>

            {{-- 5.1.4 Standardized form(s) of name according to other rules (repeatable) --}}
            <div class="mb-3">
              <label class="form-label">5.1.4 Standardized form(s) of name according to other rules <span class="badge bg-secondary ms-1">Optional</span></label>
              <div id="standardized-names-container">
                <div class="input-group mb-2 repeatable-name-row">
                  <input type="text" name="standardized_names[0]" class="form-control" value="{{ old('standardized_names.0') }}">
                  <button type="button" class="btn btn-outline-secondary remove-name-row" aria-label="Remove"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary add-name-row" data-container="standardized-names-container" data-prefix="standardized_names">
                <i class="fas fa-plus me-1" aria-hidden="true"></i>Add new
              </button>
              <div class="form-text text-muted small">"Record the standardized form of name for the entity being described in accordance with other conventions or rules. Specify the rules and/or if appropriate the name of the agency by which these standardized forms of name have been constructed." (ISAAR 5.1.4)</div>
            </div>

            {{-- 5.1.5 Other form(s) of name (repeatable) --}}
            <div class="mb-3">
              <label class="form-label">5.1.5 Other form(s) of name <span class="badge bg-secondary ms-1">Optional</span></label>
              <div id="other-names-container">
                <div class="input-group mb-2 repeatable-name-row">
                  <input type="text" name="other_names[0]" class="form-control" value="{{ old('other_names.0') }}">
                  <button type="button" class="btn btn-outline-secondary remove-name-row" aria-label="Remove"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary add-name-row" data-container="other-names-container" data-prefix="other_names">
                <i class="fas fa-plus me-1" aria-hidden="true"></i>Add new
              </button>
              <div class="form-text text-muted small">"Indicate any other name(s) for the corporate body, person or family not used elsewhere in the Identity Area." Examples include acronyms, previous names, pseudonyms, maiden names and titles of nobility or honour. (ISAAR 5.1.5)</div>
            </div>

            <div class="mb-3">
              <label for="identifier" class="form-label">5.1.6 Identifiers for corporate bodies <span class="badge bg-secondary ms-1">Optional</span></label>
              <input type="text" name="identifier" id="identifier" class="form-control"
                     value="{{ old('identifier') }}">
              <div class="form-text text-muted small">"Record where possible any official number or other identifier (e.g. a company registration number) for the corporate body and reference the jurisdiction and scheme under which it has been allocated." (ISAAR 5.1.6)</div>
            </div>

          </div>
        </div>
      </div>

      {{-- ===== 5.2 Description Area ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="description-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#description-collapse" aria-expanded="false" aria-controls="description-collapse">
            5.2 Description Area
          </button>
        </h2>
        <div id="description-collapse" class="accordion-collapse collapse" aria-labelledby="description-heading" data-bs-parent="#isaarAccordion">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="dates_of_existence" class="form-label">
                5.2.1 Dates of existence
                <span class="text-danger">*</span> <span class="badge bg-danger ms-1">Required</span>
              </label>
              <input type="text" name="dates_of_existence" id="dates_of_existence" class="form-control @error('dates_of_existence') is-invalid @enderror"
                     value="{{ old('dates_of_existence') }}">
              @error('dates_of_existence') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text text-muted small">"Record the dates of existence of the entity being described. For corporate bodies include the date of establishment/foundation/enabling legislation and dissolution. For persons include the dates or approximate dates of birth and death or, when these dates are not known, floruit dates." (ISAAR 5.2.1)</div>
            </div>

            <div class="mb-3">
              <label for="history" class="form-label">5.2.2 History <span class="badge bg-secondary ms-1">Optional</span></label>
              <textarea name="history" id="history" class="form-control" rows="6">{{ old('history') }}</textarea>
              <div class="form-text text-muted small">"Record in narrative form or as a chronology the main life events, activities, achievements and/or roles of the entity being described. This may include information on gender, nationality, family and religious or political affiliations." (ISAAR 5.2.2)</div>
            </div>

            <div class="mb-3">
              <label for="places" class="form-label">5.2.3 Places <span class="badge bg-warning ms-1">Recommended</span></label>
              <textarea name="places" id="places" class="form-control" rows="4">{{ old('places') }}</textarea>
              <div class="form-text text-muted small">"Record the name of the predominant place(s)/jurisdiction(s), together with the nature and covering dates of the relationship with the entity." (ISAAR 5.2.3)</div>
            </div>

            <div class="mb-3">
              <label for="legal_status" class="form-label">5.2.4 Legal status <span class="badge bg-warning ms-1">Recommended</span></label>
              <textarea name="legal_status" id="legal_status" class="form-control" rows="4">{{ old('legal_status') }}</textarea>
              <div class="form-text text-muted small">"Record the legal status and where appropriate the type of corporate body together with the covering dates when this status applied." (ISAAR 5.2.4)</div>
            </div>

            <div class="mb-3">
              <label for="functions" class="form-label">5.2.5 Functions, occupations and activities <span class="badge bg-warning ms-1">Recommended</span></label>
              <textarea name="functions" id="functions" class="form-control" rows="4">{{ old('functions') }}</textarea>
              <div class="form-text text-muted small">"Record the functions, occupations and activities performed by the entity being described, together with the covering dates when useful." (ISAAR 5.2.5)</div>
            </div>

            <div class="mb-3">
              <label for="mandates" class="form-label">5.2.6 Mandates/sources of authority <span class="badge bg-warning ms-1">Recommended</span></label>
              <textarea name="mandates" id="mandates" class="form-control" rows="4">{{ old('mandates') }}</textarea>
              <div class="form-text text-muted small">"Record any document, law, directive or charter which acts as a source of authority for the powers, functions and responsibilities of the entity being described." (ISAAR 5.2.6)</div>
            </div>

            <div class="mb-3">
              <label for="internal_structures" class="form-label">5.2.7 Internal structures/genealogy <span class="badge bg-warning ms-1">Recommended</span></label>
              <textarea name="internal_structures" id="internal_structures" class="form-control" rows="4">{{ old('internal_structures') }}</textarea>
              <div class="form-text text-muted small">"Describe the internal structure of a corporate body and the dates of any changes to that structure that are significant to the understanding of the way that corporate body conducted its affairs. Describe the genealogy of a family in a way that demonstrates the inter-relationships of its members with covering dates." (ISAAR 5.2.7)</div>
            </div>

            <div class="mb-3">
              <label for="general_context" class="form-label">5.2.8 General context <span class="badge bg-warning ms-1">Recommended</span></label>
              <textarea name="general_context" id="general_context" class="form-control" rows="4">{{ old('general_context') }}</textarea>
              <div class="form-text text-muted small">"Provide any significant information on the social, cultural, economic, political and/or historical context in which the entity being described operated." (ISAAR 5.2.8)</div>
            </div>

          </div>
        </div>
      </div>

      {{-- ===== 5.3 Relationships Area ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="relationships-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#relationships-collapse" aria-expanded="false" aria-controls="relationships-collapse">
            5.3 Relationships Area
          </button>
        </h2>
        <div id="relationships-collapse" class="accordion-collapse collapse" aria-labelledby="relationships-heading" data-bs-parent="#isaarAccordion">
          <div class="accordion-body">

            <h3 class="fs-6 mb-2">5.3.1 Related corporate bodies, persons or families</h3>
            <div class="table-responsive mb-3">
              <table class="table table-bordered mb-0" id="related-actors-table" aria-label="Related actors">
                <thead class="table-light">
                  <tr>
                    <th scope="col" style="width:25%">Name</th>
                    <th scope="col" style="width:15%">Category</th>
                    <th scope="col" style="width:15%">Type</th>
                    <th scope="col" style="width:15%">Dates</th>
                    <th scope="col" style="width:25%">Description</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><input type="text" name="relatedActors[0][name]" class="form-control form-control-sm" placeholder="Type to search..." value="{{ old('relatedActors.0.name') }}"></td>
                    <td>
                      <select name="relatedActors[0][category]" class="form-select form-select-sm">
                        <option value="">-- Select --</option>
                        <option value="hierarchical" {{ old('relatedActors.0.category') === 'hierarchical' ? 'selected' : '' }}>Hierarchical</option>
                        <option value="temporal" {{ old('relatedActors.0.category') === 'temporal' ? 'selected' : '' }}>Temporal</option>
                        <option value="family" {{ old('relatedActors.0.category') === 'family' ? 'selected' : '' }}>Family</option>
                        <option value="associative" {{ old('relatedActors.0.category') === 'associative' ? 'selected' : '' }}>Associative</option>
                      </select>
                    </td>
                    <td><input type="text" name="relatedActors[0][type]" class="form-control form-control-sm" value="{{ old('relatedActors.0.type') }}"></td>
                    <td><input type="text" name="relatedActors[0][dates]" class="form-control form-control-sm" value="{{ old('relatedActors.0.dates') }}"></td>
                    <td><input type="text" name="relatedActors[0][description]" class="form-control form-control-sm" value="{{ old('relatedActors.0.description') }}"></td>
                    <td>
                      <button type="button" class="btn btn-outline-secondary btn-sm remove-relactor-row" aria-label="Delete row">
                        <i class="fas fa-times" aria-hidden="true"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="6">
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="add-relactor-row">
                        <i class="fas fa-plus me-1" aria-hidden="true"></i>Add new
                      </button>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>
            <div class="form-text text-muted small mb-3">"Record the name of the related entity and describe the nature of the relationship. Apply appropriate conventions to describe the relationship, its type, and the applicable dates." (ISAAR 5.3.1&#8211;5.3.4)</div>

            <h3 class="fs-6 mb-2">Related resources</h3>
            <div class="table-responsive mb-3">
              <table class="table table-bordered mb-0" id="related-resources-table" aria-label="Related resources">
                <thead class="table-light">
                  <tr>
                    <th scope="col" style="width:40%">Title</th>
                    <th scope="col" style="width:30%">Relationship</th>
                    <th scope="col" style="width:25%">Dates</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><input type="text" name="relatedResources[0][title]" class="form-control form-control-sm" placeholder="Type to search..." value="{{ old('relatedResources.0.title') }}"></td>
                    <td><input type="text" name="relatedResources[0][relationship]" class="form-control form-control-sm" value="{{ old('relatedResources.0.relationship') }}"></td>
                    <td><input type="text" name="relatedResources[0][dates]" class="form-control form-control-sm" value="{{ old('relatedResources.0.dates') }}"></td>
                    <td>
                      <button type="button" class="btn btn-outline-secondary btn-sm remove-relresource-row" aria-label="Delete row">
                        <i class="fas fa-times" aria-hidden="true"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="4">
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="add-relresource-row">
                        <i class="fas fa-plus me-1" aria-hidden="true"></i>Add new
                      </button>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

          </div>
        </div>
      </div>

      {{-- ===== Contact Information ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="contact-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#contact-collapse" aria-expanded="false" aria-controls="contact-collapse">
            Contact Information
          </button>
        </h2>
        <div id="contact-collapse" class="accordion-collapse collapse" aria-labelledby="contact-heading" data-bs-parent="#isaarAccordion">
          <div class="accordion-body">

            <div id="contacts-container">
              <div class="contact-entry" data-index="0">
                <div class="card mb-4">
                  <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Contact #<span class="contact-number">1</span></h5>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-contact" style="display:none;">
                      <i class="fas fa-trash" aria-hidden="true"></i> Remove
                    </button>
                  </div>
                  <div class="card-body">

                    <div class="row">
                      <div class="col-md-2 mb-3">
                        <label class="form-label">Title <span class="badge bg-secondary ms-1">Optional</span></label>
                        <select name="contacts[0][title]" class="form-select">
                          <option value="">Select...</option>
                          <option value="Mr" {{ old('contacts.0.title') === 'Mr' ? 'selected' : '' }}>Mr</option>
                          <option value="Mrs" {{ old('contacts.0.title') === 'Mrs' ? 'selected' : '' }}>Mrs</option>
                          <option value="Ms" {{ old('contacts.0.title') === 'Ms' ? 'selected' : '' }}>Ms</option>
                          <option value="Miss" {{ old('contacts.0.title') === 'Miss' ? 'selected' : '' }}>Miss</option>
                          <option value="Dr" {{ old('contacts.0.title') === 'Dr' ? 'selected' : '' }}>Dr</option>
                          <option value="Prof" {{ old('contacts.0.title') === 'Prof' ? 'selected' : '' }}>Prof</option>
                          <option value="Rev" {{ old('contacts.0.title') === 'Rev' ? 'selected' : '' }}>Rev</option>
                          <option value="Hon" {{ old('contacts.0.title') === 'Hon' ? 'selected' : '' }}>Hon</option>
                          <option value="Sir" {{ old('contacts.0.title') === 'Sir' ? 'selected' : '' }}>Sir</option>
                          <option value="Dame" {{ old('contacts.0.title') === 'Dame' ? 'selected' : '' }}>Dame</option>
                          <option value="Adv" {{ old('contacts.0.title') === 'Adv' ? 'selected' : '' }}>Adv</option>
                        </select>
                      </div>
                      <div class="col-md-5 mb-3">
                        <label class="form-label">Contact person <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="text" name="contacts[0][contact_person]" class="form-control" value="{{ old('contacts.0.contact_person') }}">
                      </div>
                      <div class="col-md-5 mb-3">
                        <label class="form-label">Role/Position <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="text" name="contacts[0][role]" class="form-control" value="{{ old('contacts.0.role') }}" placeholder="e.g., Director, Manager, Curator">
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Department <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="text" name="contacts[0][department]" class="form-control" value="{{ old('contacts.0.department') }}">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Contact type <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="text" name="contacts[0][contact_type]" class="form-control" value="{{ old('contacts.0.contact_type') }}" placeholder="e.g., Primary, Business, Home">
                      </div>
                    </div>

                    <hr>

                    <div class="row">
                      <div class="col-md-4 mb-3">
                        <label class="form-label">Telephone <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="tel" name="contacts[0][telephone]" class="form-control" value="{{ old('contacts.0.telephone') }}">
                      </div>
                      <div class="col-md-4 mb-3">
                        <label class="form-label">Cell/Mobile <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="tel" name="contacts[0][cell]" class="form-control" value="{{ old('contacts.0.cell') }}">
                      </div>
                      <div class="col-md-4 mb-3">
                        <label class="form-label">Fax <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="tel" name="contacts[0][fax]" class="form-control" value="{{ old('contacts.0.fax') }}">
                      </div>
                    </div>

                    <div class="row">
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Email <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="email" name="contacts[0][email]" class="form-control" value="{{ old('contacts.0.email') }}">
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Website <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="url" name="contacts[0][website]" class="form-control" value="{{ old('contacts.0.website') }}" placeholder="https://">
                      </div>
                    </div>

                    <hr>
                    <h6 class="text-muted mb-3">Physical address</h6>

                    <div class="mb-3">
                      <label class="form-label">Street address <span class="badge bg-secondary ms-1">Optional</span></label>
                      <textarea name="contacts[0][street_address]" class="form-control" rows="2">{{ old('contacts.0.street_address') }}</textarea>
                    </div>

                    <div class="row">
                      <div class="col-md-3 mb-3">
                        <label class="form-label">City <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="text" name="contacts[0][city]" class="form-control" value="{{ old('contacts.0.city') }}">
                      </div>
                      <div class="col-md-3 mb-3">
                        <label class="form-label">Region/Province <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="text" name="contacts[0][region]" class="form-control" value="{{ old('contacts.0.region') }}">
                      </div>
                      <div class="col-md-3 mb-3">
                        <label class="form-label">Postal code <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="text" name="contacts[0][postal_code]" class="form-control" value="{{ old('contacts.0.postal_code') }}">
                      </div>
                      <div class="col-md-3 mb-3">
                        <label class="form-label">Country <span class="badge bg-secondary ms-1">Optional</span></label>
                        <input type="text" name="contacts[0][country_code]" class="form-control" value="{{ old('contacts.0.country_code') }}" placeholder="e.g. ZA, US, GB">
                      </div>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Note <span class="badge bg-secondary ms-1">Optional</span></label>
                      <textarea name="contacts[0][note]" class="form-control" rows="2">{{ old('contacts.0.note') }}</textarea>
                    </div>

                    <div class="form-check">
                      <input type="checkbox" name="contacts[0][primary_contact]" value="1" class="form-check-input primary-contact-check"
                             {{ old('contacts.0.primary_contact') ? 'checked' : '' }}>
                      <label class="form-check-label">Primary contact</label>
                    </div>

                  </div>
                </div>
              </div>
            </div>

            <button type="button" id="add-contact" class="btn btn-outline-secondary">
              <i class="fas fa-plus me-1" aria-hidden="true"></i>Add another contact
            </button>

          </div>
        </div>
      </div>

      {{-- ===== Access Points ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="access-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#access-collapse" aria-expanded="false" aria-controls="access-collapse">
            Access Points
          </button>
        </h2>
        <div id="access-collapse" class="accordion-collapse collapse" aria-labelledby="access-heading" data-bs-parent="#isaarAccordion">
          <div class="accordion-body">

            <div class="mb-3">
              <label class="form-label">Subject access points <span class="badge bg-secondary ms-1">Optional</span></label>
              <div id="subject-access-container">
                <div class="input-group mb-2 repeatable-name-row">
                  <input type="text" name="subject_access_points[0]" class="form-control" placeholder="Type a subject..." value="{{ old('subject_access_points.0') }}">
                  <button type="button" class="btn btn-outline-secondary remove-name-row" aria-label="Remove"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary add-name-row" data-container="subject-access-container" data-prefix="subject_access_points">
                <i class="fas fa-plus me-1" aria-hidden="true"></i>Add new
              </button>
            </div>

            <div class="mb-3">
              <label class="form-label">Place access points <span class="badge bg-secondary ms-1">Optional</span></label>
              <div id="place-access-container">
                <div class="input-group mb-2 repeatable-name-row">
                  <input type="text" name="place_access_points[0]" class="form-control" placeholder="Type a place..." value="{{ old('place_access_points.0') }}">
                  <button type="button" class="btn btn-outline-secondary remove-name-row" aria-label="Remove"><i class="fas fa-times" aria-hidden="true"></i></button>
                </div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-secondary add-name-row" data-container="place-access-container" data-prefix="place_access_points">
                <i class="fas fa-plus me-1" aria-hidden="true"></i>Add new
              </button>
            </div>

            <h3 class="fs-6 mb-2">Occupation(s)</h3>
            <div class="table-responsive">
              <table class="table table-bordered mb-0" id="occupations-table" aria-label="Occupations">
                <thead class="table-light">
                  <tr>
                    <th scope="col" style="width:50%">Occupation</th>
                    <th scope="col" style="width:45%">Note</th>
                    <th scope="col"><span class="visually-hidden">Delete</span></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><input type="text" name="occupations[0][occupation]" class="form-control form-control-sm" placeholder="Type occupation..." value="{{ old('occupations.0.occupation') }}"></td>
                    <td><input type="text" name="occupations[0][note]" class="form-control form-control-sm" value="{{ old('occupations.0.note') }}"></td>
                    <td>
                      <button type="button" class="btn btn-outline-secondary btn-sm remove-occupation-row" aria-label="Delete row">
                        <i class="fas fa-times" aria-hidden="true"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr>
                    <td colspan="3">
                      <button type="button" class="btn btn-sm btn-outline-secondary" id="add-occupation-row">
                        <i class="fas fa-plus me-1" aria-hidden="true"></i>Add new
                      </button>
                    </td>
                  </tr>
                </tfoot>
              </table>
            </div>

          </div>
        </div>
      </div>

      {{-- ===== 5.4 Control Area ===== --}}
      <div class="accordion-item">
        <h2 class="accordion-header" id="control-heading">
          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#control-collapse" aria-expanded="false" aria-controls="control-collapse">
            5.4 Control Area
          </button>
        </h2>
        <div id="control-collapse" class="accordion-collapse collapse" aria-labelledby="control-heading" data-bs-parent="#isaarAccordion">
          <div class="accordion-body">

            <div class="mb-3">
              <label for="description_identifier" class="form-label">
                5.4.1 Authority record identifier
                <span class="text-danger">*</span> <span class="badge bg-danger ms-1">Required</span>
              </label>
              <input type="text" name="description_identifier" id="description_identifier" class="form-control @error('description_identifier') is-invalid @enderror"
                     value="{{ old('description_identifier') }}">
              @error('description_identifier') <div class="invalid-feedback">{{ $message }}</div> @enderror
              <div class="form-text text-muted small">"Record a unique authority record identifier in accordance with local and/or national conventions. If the authority record is to be used internationally, record the country code of the country in which the authority record was created in accordance with the latest version of ISO 3166." (ISAAR 5.4.1)</div>
            </div>

            <div class="mb-3">
              <label for="institution_responsible" class="form-label">5.4.2 Institution identifiers <span class="badge bg-secondary ms-1">Optional</span></label>
              <input type="text" name="institution_responsible" id="institution_responsible" class="form-control"
                     value="{{ old('institution_responsible') }}">
              <div class="form-text text-muted small">"Record the full authorized form of name(s) of the agency(ies) responsible for creating, modifying or disseminating the authority record or, alternatively, record a code for the agency in accordance with the national or international agency code standard." (ISAAR 5.4.2)</div>
            </div>

            <div class="mb-3">
              <label for="rules" class="form-label">5.4.3 Rules and/or conventions used <span class="badge bg-secondary ms-1">Optional</span></label>
              <textarea name="rules" id="rules" class="form-control" rows="4">{{ old('rules') }}</textarea>
              <div class="form-text text-muted small">"Record the names and where useful the editions or publication dates of the conventions or rules applied. Specify separately which rules have been applied for creating the Authorized form of name." (ISAAR 5.4.3)</div>
            </div>

            <div class="mb-3">
              <label for="description_status" class="form-label">5.4.4 Status <span class="badge bg-secondary ms-1">Optional</span></label>
              <select name="description_status" id="description_status" class="form-select">
                <option value="">-- Select --</option>
                <option value="draft" {{ old('description_status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="revised" {{ old('description_status') === 'revised' ? 'selected' : '' }}>Revised</option>
                <option value="final" {{ old('description_status') === 'final' ? 'selected' : '' }}>Final</option>
              </select>
              <div class="form-text text-muted small">"Indicate the drafting status of the authority record so that users can understand the current status of the authority record." (ISAAR 5.4.4)</div>
            </div>

            <div class="mb-3">
              <label for="description_detail" class="form-label">5.4.5 Level of detail <span class="badge bg-secondary ms-1">Optional</span></label>
              <select name="description_detail" id="description_detail" class="form-select">
                <option value="">-- Select --</option>
                <option value="full" {{ old('description_detail') === 'full' ? 'selected' : '' }}>Full</option>
                <option value="partial" {{ old('description_detail') === 'partial' ? 'selected' : '' }}>Partial</option>
                <option value="minimal" {{ old('description_detail') === 'minimal' ? 'selected' : '' }}>Minimal</option>
              </select>
              <div class="form-text text-muted small">"Minimal records are those that consist only of the four essential elements of an ISAAR(CPF) compliant authority record, while full records convey information for all relevant ISAAR(CPF) elements of description." (ISAAR 5.4.5)</div>
            </div>

            <div class="mb-3">
              <label for="revision_history" class="form-label">5.4.6 Dates of creation, revision and deletion <span class="badge bg-secondary ms-1">Optional</span></label>
              <textarea name="revision_history" id="revision_history" class="form-control" rows="4">{{ old('revision_history') }}</textarea>
              <div class="form-text text-muted small">"Record the date the authority record was created and the dates of any revisions to the record." (ISAAR 5.4.6)</div>
            </div>

            <div class="mb-3">
              <label for="language" class="form-label">5.4.7 Language(s) <span class="badge bg-secondary ms-1">Optional</span></label>
              <select name="language[]" id="language" class="form-select" multiple size="4" aria-describedby="language-help">
                @php
                  $allLanguages = [
                    'aa'=>'Afar','ab'=>'Abkhazian','af'=>'Afrikaans','am'=>'Amharic','ar'=>'Arabic','as'=>'Assamese',
                    'ay'=>'Aymara','az'=>'Azerbaijani','ba'=>'Bashkir','be'=>'Belarusian','bg'=>'Bulgarian','bh'=>'Bihari',
                    'bn'=>'Bengali','bo'=>'Tibetan','br'=>'Breton','ca'=>'Catalan','co'=>'Corsican','cs'=>'Czech',
                    'cy'=>'Welsh','da'=>'Danish','de'=>'German','dz'=>'Dzongkha','el'=>'Greek','en'=>'English',
                    'eo'=>'Esperanto','es'=>'Spanish','et'=>'Estonian','eu'=>'Basque','fa'=>'Persian','fi'=>'Finnish',
                    'fj'=>'Fijian','fo'=>'Faroese','fr'=>'French','fy'=>'Frisian','ga'=>'Irish','gd'=>'Scottish Gaelic',
                    'gl'=>'Galician','gn'=>'Guarani','gu'=>'Gujarati','ha'=>'Hausa','he'=>'Hebrew','hi'=>'Hindi',
                    'hr'=>'Croatian','hu'=>'Hungarian','hy'=>'Armenian','ia'=>'Interlingua','id'=>'Indonesian',
                    'ik'=>'Inupiaq','is'=>'Icelandic','it'=>'Italian','iu'=>'Inuktitut','ja'=>'Japanese','jv'=>'Javanese',
                    'ka'=>'Georgian','kk'=>'Kazakh','kl'=>'Kalaallisut','km'=>'Khmer','kn'=>'Kannada','ko'=>'Korean',
                    'ks'=>'Kashmiri','ku'=>'Kurdish','ky'=>'Kyrgyz','la'=>'Latin','ln'=>'Lingala','lo'=>'Lao',
                    'lt'=>'Lithuanian','lv'=>'Latvian','mg'=>'Malagasy','mi'=>'Maori','mk'=>'Macedonian','ml'=>'Malayalam',
                    'mn'=>'Mongolian','mr'=>'Marathi','ms'=>'Malay','mt'=>'Maltese','my'=>'Burmese','na'=>'Nauru',
                    'ne'=>'Nepali','nl'=>'Dutch','no'=>'Norwegian','nr'=>'South Ndebele','nso'=>'Northern Sotho',
                    'oc'=>'Occitan','om'=>'Oromo','or'=>'Oriya','pa'=>'Punjabi','pl'=>'Polish','ps'=>'Pashto',
                    'pt'=>'Portuguese','qu'=>'Quechua','rm'=>'Romansh','rn'=>'Rundi','ro'=>'Romanian','ru'=>'Russian',
                    'rw'=>'Kinyarwanda','sa'=>'Sanskrit','sd'=>'Sindhi','si'=>'Sinhala','sk'=>'Slovak','sl'=>'Slovenian',
                    'sm'=>'Samoan','sn'=>'Shona','so'=>'Somali','sq'=>'Albanian','sr'=>'Serbian','ss'=>'Swati',
                    'st'=>'Southern Sotho','su'=>'Sundanese','sv'=>'Swedish','sw'=>'Swahili','ta'=>'Tamil','te'=>'Telugu',
                    'tg'=>'Tajik','th'=>'Thai','ti'=>'Tigrinya','tk'=>'Turkmen','tl'=>'Tagalog','tn'=>'Tswana',
                    'to'=>'Tongan','tr'=>'Turkish','ts'=>'Tsonga','tt'=>'Tatar','tw'=>'Twi','uk'=>'Ukrainian',
                    'ur'=>'Urdu','uz'=>'Uzbek','ve'=>'Venda','vi'=>'Vietnamese','vo'=>'Volapuk','wo'=>'Wolof',
                    'xh'=>'Xhosa','yi'=>'Yiddish','yo'=>'Yoruba','za'=>'Zhuang','zh'=>'Chinese','zu'=>'Zulu',
                  ];
                @endphp
                @foreach($allLanguages as $code => $name)
                  <option value="{{ $code }}" {{ in_array($code, old('language', [])) ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
              </select>
              <div class="form-text text-muted small" id="language-help">Hold Ctrl/Cmd to select multiple. (ISAAR 5.4.7)</div>
            </div>

            <div class="mb-3">
              <label for="script" class="form-label">5.4.7 Script(s) <span class="badge bg-secondary ms-1">Optional</span></label>
              <select name="script[]" id="script" class="form-select" multiple size="4" aria-describedby="script-help">
                @php
                  $allScripts = [
                    'Arab'=>'Arabic','Armn'=>'Armenian','Beng'=>'Bengali','Bopo'=>'Bopomofo','Cyrl'=>'Cyrillic',
                    'Deva'=>'Devanagari','Ethi'=>'Ethiopic','Geor'=>'Georgian','Grek'=>'Greek','Gujr'=>'Gujarati',
                    'Guru'=>'Gurmukhi','Hang'=>'Hangul','Hani'=>'Han','Hans'=>'Han (Simplified)','Hant'=>'Han (Traditional)',
                    'Hebr'=>'Hebrew','Hira'=>'Hiragana','Jpan'=>'Japanese','Kana'=>'Katakana','Khmr'=>'Khmer',
                    'Knda'=>'Kannada','Kore'=>'Korean','Laoo'=>'Lao','Latn'=>'Latin','Mlym'=>'Malayalam',
                    'Mong'=>'Mongolian','Mymr'=>'Myanmar','Orya'=>'Oriya','Sinh'=>'Sinhala','Taml'=>'Tamil',
                    'Telu'=>'Telugu','Thaa'=>'Thaana','Thai'=>'Thai','Tibt'=>'Tibetan','Zmth'=>'Mathematical',
                    'Zsym'=>'Symbols','Zyyy'=>'Common',
                  ];
                @endphp
                @foreach($allScripts as $code => $name)
                  <option value="{{ $code }}" {{ in_array($code, old('script', [])) ? 'selected' : '' }}>{{ $name }} ({{ $code }})</option>
                @endforeach
              </select>
              <div class="form-text text-muted small" id="script-help">Hold Ctrl/Cmd to select multiple. (ISAAR 5.4.7)</div>
            </div>

            <div class="mb-3">
              <label for="sources" class="form-label">5.4.8 Sources <span class="badge bg-warning ms-1">Recommended</span></label>
              <textarea name="sources" id="sources" class="form-control" rows="4">{{ old('sources') }}</textarea>
              <div class="form-text text-muted small">"Record the sources consulted in establishing the authority record." (ISAAR 5.4.8)</div>
            </div>

            <div class="mb-3">
              <label for="maintenance_notes" class="form-label">5.4.9 Maintenance notes <span class="badge bg-warning ms-1">Recommended</span></label>
              <textarea name="maintenance_notes" id="maintenance_notes" class="form-control" rows="4">{{ old('maintenance_notes') }}</textarea>
              <div class="form-text text-muted small">"Record notes pertinent to the creation and maintenance of the authority record. The names of persons responsible for creating the authority record may be recorded here." (ISAAR 5.4.9)</div>
            </div>

          </div>
        </div>
      </div>

    </div>

    <div class="mt-3 mb-5">
      <button type="submit" class="btn btn-primary">Create Family</button>
      <a href="{{ route('families.index') }}" class="btn btn-secondary ms-2">Cancel</a>
    </div>
  </form>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {

  /* === Repeatable name rows (parallel, standardized, other names, subject, place) === */
  var nameCounters = {};
  document.querySelectorAll('[id$="-container"]').forEach(function(c) {
    nameCounters[c.id] = c.querySelectorAll('.repeatable-name-row').length;
  });

  document.querySelectorAll('.add-name-row').forEach(function(btn) {
    btn.addEventListener('click', function() {
      var containerId = btn.dataset.container;
      var prefix = btn.dataset.prefix;
      var container = document.getElementById(containerId);
      var idx = nameCounters[containerId] || 1;
      nameCounters[containerId] = idx + 1;
      var div = document.createElement('div');
      div.className = 'input-group mb-2 repeatable-name-row';
      div.innerHTML = '<input type="text" name="' + prefix + '[' + idx + ']" class="form-control" value="">' +
        '<button type="button" class="btn btn-outline-secondary remove-name-row" aria-label="Remove"><i class="fas fa-times" aria-hidden="true"></i></button>';
      container.appendChild(div);
    });
  });

  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.remove-name-row');
    if (btn) {
      var container = btn.closest('[id$="-container"]');
      if (container && container.querySelectorAll('.repeatable-name-row').length > 1) {
        btn.closest('.repeatable-name-row').remove();
      } else {
        var input = btn.closest('.repeatable-name-row').querySelector('input[type="text"]');
        if (input) input.value = '';
      }
    }
  });

  /* === Related actors multi-row === */
  var relActorIdx = 1;
  document.getElementById('add-relactor-row').addEventListener('click', function() {
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><input type="text" name="relatedActors[' + relActorIdx + '][name]" class="form-control form-control-sm" placeholder="Type to search..."></td>' +
      '<td><select name="relatedActors[' + relActorIdx + '][category]" class="form-select form-select-sm"><option value="">-- Select --</option><option value="hierarchical">Hierarchical</option><option value="temporal">Temporal</option><option value="family">Family</option><option value="associative">Associative</option></select></td>' +
      '<td><input type="text" name="relatedActors[' + relActorIdx + '][type]" class="form-control form-control-sm"></td>' +
      '<td><input type="text" name="relatedActors[' + relActorIdx + '][dates]" class="form-control form-control-sm"></td>' +
      '<td><input type="text" name="relatedActors[' + relActorIdx + '][description]" class="form-control form-control-sm"></td>' +
      '<td><button type="button" class="btn btn-outline-secondary btn-sm remove-relactor-row" aria-label="Delete row"><i class="fas fa-times" aria-hidden="true"></i></button></td>';
    document.querySelector('#related-actors-table tbody').appendChild(tr);
    relActorIdx++;
  });

  /* === Related resources multi-row === */
  var relResIdx = 1;
  document.getElementById('add-relresource-row').addEventListener('click', function() {
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><input type="text" name="relatedResources[' + relResIdx + '][title]" class="form-control form-control-sm" placeholder="Type to search..."></td>' +
      '<td><input type="text" name="relatedResources[' + relResIdx + '][relationship]" class="form-control form-control-sm"></td>' +
      '<td><input type="text" name="relatedResources[' + relResIdx + '][dates]" class="form-control form-control-sm"></td>' +
      '<td><button type="button" class="btn btn-outline-secondary btn-sm remove-relresource-row" aria-label="Delete row"><i class="fas fa-times" aria-hidden="true"></i></button></td>';
    document.querySelector('#related-resources-table tbody').appendChild(tr);
    relResIdx++;
  });

  /* === Occupations multi-row === */
  var occIdx = 1;
  document.getElementById('add-occupation-row').addEventListener('click', function() {
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><input type="text" name="occupations[' + occIdx + '][occupation]" class="form-control form-control-sm" placeholder="Type occupation..."></td>' +
      '<td><input type="text" name="occupations[' + occIdx + '][note]" class="form-control form-control-sm"></td>' +
      '<td><button type="button" class="btn btn-outline-secondary btn-sm remove-occupation-row" aria-label="Delete row"><i class="fas fa-times" aria-hidden="true"></i></button></td>';
    document.querySelector('#occupations-table tbody').appendChild(tr);
    occIdx++;
  });

  /* === Remove row handler for all multi-row tables === */
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.remove-relactor-row, .remove-relresource-row, .remove-occupation-row');
    if (btn) {
      var table = btn.closest('table');
      if (table.querySelectorAll('tbody tr').length > 1) {
        btn.closest('tr').remove();
      }
    }
  });

  /* === Contacts add/remove === */
  var contactIdx = 1;
  var contactsContainer = document.getElementById('contacts-container');

  document.getElementById('add-contact').addEventListener('click', function() {
    var visibleCount = contactsContainer.querySelectorAll('.contact-entry').length;
    var newNumber = visibleCount + 1;
    var html = document.getElementById('contact-template').innerHTML
      .replace(/__INDEX__/g, contactIdx)
      .replace(/__NUMBER__/g, newNumber);
    var div = document.createElement('div');
    div.innerHTML = html;
    contactsContainer.appendChild(div.firstElementChild);
    contactIdx++;
    updateContactNumbers();
    contactsContainer.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  contactsContainer.addEventListener('click', function(e) {
    if (e.target.closest('.remove-contact')) {
      var entry = e.target.closest('.contact-entry');
      if (contactsContainer.querySelectorAll('.contact-entry').length > 1) {
        entry.remove();
        updateContactNumbers();
      }
    }
  });

  contactsContainer.addEventListener('change', function(e) {
    if (e.target.classList.contains('primary-contact-check') && e.target.checked) {
      contactsContainer.querySelectorAll('.primary-contact-check').forEach(function(cb) {
        if (cb !== e.target) cb.checked = false;
      });
    }
  });

  function updateContactNumbers() {
    var entries = contactsContainer.querySelectorAll('.contact-entry');
    entries.forEach(function(entry, i) {
      var numSpan = entry.querySelector('.contact-number');
      if (numSpan) numSpan.textContent = i + 1;
      var removeBtn = entry.querySelector('.remove-contact');
      if (removeBtn) removeBtn.style.display = entries.length > 1 ? '' : 'none';
    });
  }
  updateContactNumbers();
});
</script>
@endpush

<template id="contact-template">
  <div class="contact-entry" data-index="__INDEX__">
    <div class="card mb-4">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Contact #<span class="contact-number">__NUMBER__</span></h5>
        <button type="button" class="btn btn-sm btn-outline-danger remove-contact">
          <i class="fas fa-trash" aria-hidden="true"></i> Remove
        </button>
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-2 mb-3">
            <label class="form-label">Title <span class="badge bg-secondary ms-1">Optional</span></label>
            <select name="contacts[__INDEX__][title]" class="form-select">
              <option value="">Select...</option>
              <option value="Mr">Mr</option><option value="Mrs">Mrs</option><option value="Ms">Ms</option>
              <option value="Miss">Miss</option><option value="Dr">Dr</option><option value="Prof">Prof</option>
              <option value="Rev">Rev</option><option value="Hon">Hon</option><option value="Sir">Sir</option>
              <option value="Dame">Dame</option><option value="Adv">Adv</option>
            </select>
          </div>
          <div class="col-md-5 mb-3">
            <label class="form-label">Contact person <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="text" name="contacts[__INDEX__][contact_person]" class="form-control">
          </div>
          <div class="col-md-5 mb-3">
            <label class="form-label">Role/Position <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="text" name="contacts[__INDEX__][role]" class="form-control" placeholder="e.g., Director, Manager, Curator">
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Department <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="text" name="contacts[__INDEX__][department]" class="form-control">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Contact type <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="text" name="contacts[__INDEX__][contact_type]" class="form-control" placeholder="e.g., Primary, Business, Home">
          </div>
        </div>
        <hr>
        <div class="row">
          <div class="col-md-4 mb-3">
            <label class="form-label">Telephone <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="tel" name="contacts[__INDEX__][telephone]" class="form-control">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Cell/Mobile <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="tel" name="contacts[__INDEX__][cell]" class="form-control">
          </div>
          <div class="col-md-4 mb-3">
            <label class="form-label">Fax <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="tel" name="contacts[__INDEX__][fax]" class="form-control">
          </div>
        </div>
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label">Email <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="email" name="contacts[__INDEX__][email]" class="form-control">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label">Website <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="url" name="contacts[__INDEX__][website]" class="form-control" placeholder="https://">
          </div>
        </div>
        <hr>
        <h6 class="text-muted mb-3">Physical address</h6>
        <div class="mb-3">
          <label class="form-label">Street address <span class="badge bg-secondary ms-1">Optional</span></label>
          <textarea name="contacts[__INDEX__][street_address]" class="form-control" rows="2"></textarea>
        </div>
        <div class="row">
          <div class="col-md-3 mb-3">
            <label class="form-label">City <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="text" name="contacts[__INDEX__][city]" class="form-control">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Region/Province <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="text" name="contacts[__INDEX__][region]" class="form-control">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Postal code <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="text" name="contacts[__INDEX__][postal_code]" class="form-control">
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label">Country <span class="badge bg-secondary ms-1">Optional</span></label>
            <input type="text" name="contacts[__INDEX__][country_code]" class="form-control" placeholder="e.g. ZA, US, GB">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Note <span class="badge bg-secondary ms-1">Optional</span></label>
          <textarea name="contacts[__INDEX__][note]" class="form-control" rows="2"></textarea>
        </div>
        <div class="form-check">
          <input type="checkbox" name="contacts[__INDEX__][primary_contact]" value="1" class="form-check-input primary-contact-check">
          <label class="form-check-label">Primary contact</label>
        </div>
      </div>
    </div>
  </div>
</template>

@endsection
