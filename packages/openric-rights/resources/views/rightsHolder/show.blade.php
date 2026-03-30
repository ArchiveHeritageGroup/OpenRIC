@extends('theme::layouts.1col')

@section('title', 'View rights holder')
@section('body-class', 'view rightsholder')

@section('content')
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0">View rights holder</h1>
    <span class="small text-muted">{{ $rightsHolder->name ?: '[Untitled]' }}</span>
  </div>

  {{-- Identity area --}}
  <section class="section border-bottom" id="identityArea">
    <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">Identity area</div></h2>
    <div>
      @if($rightsHolder->name)
        <div class="field row g-0">
          <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2">Name</h3>
          <div class="col-9 p-2">{{ $rightsHolder->name }}</div>
        </div>
      @endif
      @if($rightsHolder->description_identifier ?? null)
        <div class="field row g-0">
          <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2">Identifier</h3>
          <div class="col-9 p-2">{{ $rightsHolder->description_identifier }}</div>
        </div>
      @endif
      @if($rightsHolder->entity_iri ?? null)
        <div class="field row g-0">
          <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2">Entity IRI</h3>
          <div class="col-9 p-2"><code>{{ $rightsHolder->entity_iri }}</code></div>
        </div>
      @endif
    </div>
  </section>

  {{-- Description area --}}
  <section class="section border-bottom" id="descriptionArea">
    <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">Description area</div></h2>
    <div>
      @foreach(['history', 'places', 'legal_status', 'functions', 'mandates', 'internal_structures', 'general_context'] as $field)
        @if($rightsHolder->$field ?? null)
          <div class="field row g-0">
            <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2">{{ ucfirst(str_replace('_', ' ', $field)) }}</h3>
            <div class="col-9 p-2">{!! nl2br(e($rightsHolder->$field)) !!}</div>
          </div>
        @endif
      @endforeach
    </div>
  </section>

  {{-- Contact area --}}
  <section class="section border-bottom" id="contactArea">
    <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">Contact area</div></h2>
    <div>
      @if(isset($contacts) && count($contacts) > 0)
        @foreach($contacts as $contact)
          <section class="contact-info mb-3">
            @if($contact->contact_person)
              <div class="field row g-0">
                <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2">&nbsp;</h3>
                <div class="col-9 p-2">
                  <span class="text-primary">{{ $contact->contact_person }}</span>
                  @if($contact->is_primary)
                    <span class="badge bg-secondary ms-1">Primary contact</span>
                  @endif
                </div>
              </div>
            @endif
            @foreach(['telephone' => 'Telephone', 'fax' => 'Fax', 'email' => 'Email', 'website' => 'Website'] as $f => $label)
              @if($contact->$f ?? null)
                <div class="field row g-0">
                  <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2">{{ $label }}</h3>
                  <div class="col-9 p-2">
                    @if($f === 'email')<a href="mailto:{{ $contact->$f }}">{{ $contact->$f }}</a>
                    @elseif($f === 'website')<a href="{{ $contact->$f }}" target="_blank">{{ $contact->$f }}</a>
                    @else{{ $contact->$f }}@endif
                  </div>
                </div>
              @endif
            @endforeach
            @if($contact->street_address || $contact->city || $contact->region || $contact->country_code || $contact->postal_code)
              <div class="field row g-0">
                <h3 class="h6 lh-base m-0 text-muted col-3 border-end text-end p-2">Address</h3>
                <div class="col-9 p-2">
                  @if($contact->street_address)<div>{{ $contact->street_address }}</div>@endif
                  @if($contact->city)<div>{{ $contact->city }}</div>@endif
                  @if($contact->region)<div>{{ $contact->region }}</div>@endif
                  @if($contact->postal_code)<div>{{ $contact->postal_code }}</div>@endif
                  @if($contact->country_code)<div>{{ $contact->country_code }}</div>@endif
                </div>
              </div>
            @endif
          </section>
        @endforeach
      @endif
    </div>
  </section>

  {{-- Related rights --}}
  @if(!empty($relatedRights))
    <section class="section border-bottom" id="rightsArea">
      <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">Rights area</div></h2>
      <div class="table-responsive">
        <table class="table table-bordered table-striped table-sm mb-0">
          <thead>
            <tr><th>Basis</th><th>Entity IRI</th><th>Start date</th><th>End date</th><th>Notes</th></tr>
          </thead>
          <tbody>
            @foreach($relatedRights as $right)
              <tr>
                <td>{{ ucfirst($right->rights_basis ?? '') }}</td>
                <td><code class="small">{{ $right->entity_iri ?? '' }}</code></td>
                <td>{{ $right->start_date ?? '' }}</td>
                <td>{{ $right->end_date ?? '' }}</td>
                <td>{{ $right->notes ?? '' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </section>
  @endif
@endsection

@section('after-content')
  @auth
    <ul class="actions mb-3 nav gap-2">
      <li><a href="{{ route('rights.holders.edit', $rightsHolder->id) }}" class="btn btn-outline-primary">Edit</a></li>
      <li><a href="{{ route('rights.holders.confirmDelete', $rightsHolder->id) }}" class="btn btn-outline-danger">Delete</a></li>
      <li><a href="{{ route('rights.holders.create') }}" class="btn btn-outline-primary">Add new</a></li>
    </ul>
  @endauth
@endsection
