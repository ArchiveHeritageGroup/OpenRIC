@extends('theme::layouts.1col')

@section('title', 'User ' . (is_array($user) ? ($user['authorized_form_of_name'] ?? $user['username'] ?? 'User') : ($user->authorized_form_of_name ?? $user->username ?? 'User')))
@section('body-class', 'view user')

@php
  $username = is_array($user) ? ($user['username'] ?? '') : ($user->username ?? '');
  $email = is_array($user) ? ($user['email'] ?? '') : ($user->email ?? '');
  $active = is_array($user) ? ($user['active'] ?? 1) : ($user->active ?? 1);
  $slug = is_array($user) ? ($user['slug'] ?? '') : ($user->slug ?? '');
  $userId = is_array($user) ? ($user['id'] ?? 0) : ($user->id ?? 0);
  $afon = is_array($user) ? ($user['authorized_form_of_name'] ?? '') : ($user->authorized_form_of_name ?? '');
  $contact = is_array($user) ? ($user['contact'] ?? null) : ($user->contact ?? null);
  $translateLangs = is_array($user) ? ($user['translateLanguages'] ?? []) : ($user->translateLanguages ?? []);
  $createdAt = is_array($user) ? ($user['created_at'] ?? null) : ($user->created_at ?? null);
  $updatedAt = is_array($user) ? ($user['updated_at'] ?? null) : ($user->updated_at ?? null);
@endphp

@section('title-block')
  <h1>User {{ $afon ?: $username ?: '[Unknown]' }}</h1>
  @if(!$active)
    <div class="alert alert-danger" role="alert">This user is inactive</div>
  @endif
@endsection

@section('content')

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <section id="content">

    {{-- User details --}}
    <section id="userDetails">
      <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">User details @auth<a href="{{ route('user.edit', $slug) }}" class="ms-auto text-primary opacity-75" style="font-size:.75rem;" title="Edit"><i class="fas fa-pencil-alt"></i></a>@endauth</div></h2>
      <div>
        @if($username)
          <div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">User name</div><div class="col-9 p-2">{{ $username }} @if(auth()->check() && auth()->id() === $userId) (you) @endif</div></div>
        @endif
        @if($email)
          <div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Email</div><div class="col-9 p-2">{{ $email }}</div></div>
        @endif
        @if(isset($groups) && $groups->isNotEmpty())
          <div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">User groups</div><div class="col-9 p-2"><ul class="m-0 ms-1 ps-3">@foreach($groups as $group)<li>{{ is_array($group) ? ($group['name'] ?? '') : ($group->name ?? $group) }}</li>@endforeach</ul></div></div>
        @endif
      </div>
    </section>

    {{-- Profile --}}
    @if($afon)
      <section id="userProfile" class="mt-3">
        <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">Profile</div></h2>
        <div>
          <div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Authorized form of name</div><div class="col-9 p-2">{{ $afon }}</div></div>
        </div>
      </section>
    @endif

    {{-- Contact information --}}
    @if($contact)
      @php $c = is_array($contact) ? (object) $contact : $contact; @endphp
      <section id="userContact" class="mt-3">
        <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">Contact information</div></h2>
        <div>
          @if($email)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Email</div><div class="col-9 p-2">{{ $email }}</div></div>@endif
          @if($c->telephone ?? null)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Telephone</div><div class="col-9 p-2">{{ $c->telephone }}</div></div>@endif
          @if($c->fax ?? null)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Fax</div><div class="col-9 p-2">{{ $c->fax }}</div></div>@endif
          @if($c->website ?? null)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Website</div><div class="col-9 p-2"><a href="{{ $c->website }}" target="_blank">{{ $c->website }}</a></div></div>@endif
          @if($c->street_address ?? null)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Street address</div><div class="col-9 p-2">{{ $c->street_address }}</div></div>@endif
          @if($c->city ?? null)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">City</div><div class="col-9 p-2">{{ $c->city }}</div></div>@endif
          @if($c->region ?? null)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Region/province</div><div class="col-9 p-2">{{ $c->region }}</div></div>@endif
          @if($c->postal_code ?? null)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Postal code</div><div class="col-9 p-2">{{ $c->postal_code }}</div></div>@endif
          @if($c->country_code ?? null)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Country</div><div class="col-9 p-2">{{ strtoupper($c->country_code) }}</div></div>@endif
        </div>
      </section>
    @endif

    {{-- Allowed languages for translation --}}
    @if(!empty($translateLangs))
      <section id="userTranslateLanguages" class="mt-3">
        <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">Allowed languages for translation</div></h2>
        <div><div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Languages</div><div class="col-9 p-2"><ul class="m-0 ms-1 ps-3">@foreach($translateLangs as $lang)<li>{{ strtoupper($lang) }}</li>@endforeach</ul></div></div></div>
      </section>
    @endif

    {{-- Active status --}}
    <section id="userActiveStatus" class="mt-3">
      <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">Account status</div></h2>
      <div><div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Active</div><div class="col-9 p-2">@if($active)<span class="badge bg-success">Yes</span>@else<span class="badge bg-danger">No</span>@endif</div></div></div>
    </section>

    {{-- Administration area --}}
    @if($createdAt || $updatedAt)
      <section id="userAdmin" class="mt-3">
        <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">Administration area</div></h2>
        <div>
          @if($createdAt)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Created at</div><div class="col-9 p-2">{{ \Carbon\Carbon::parse($createdAt)->format('Y-m-d H:i:s') }}</div></div>@endif
          @if($updatedAt)<div class="row g-0"><div class="col-3 border-end text-end p-2 text-muted fw-bold">Updated at</div><div class="col-9 p-2">{{ \Carbon\Carbon::parse($updatedAt)->format('Y-m-d H:i:s') }}</div></div>@endif
        </div>
      </section>
    @endif

    {{-- ACL links --}}
    @auth
      <section class="mt-3">
        <h2 class="h5 mb-0"><div class="d-flex p-3 border-bottom text-primary">Access control</div></h2>
        <div class="list-group list-group-flush">
          <a href="{{ route('user.indexActorAcl', $slug) }}" class="list-group-item list-group-item-action small">Actor permissions</a>
          <a href="{{ route('user.indexInformationObjectAcl', $slug) }}" class="list-group-item list-group-item-action small">Information object permissions</a>
          <a href="{{ route('user.indexRepositoryAcl', $slug) }}" class="list-group-item list-group-item-action small">Repository permissions</a>
          <a href="{{ route('user.indexTermAcl', $slug) }}" class="list-group-item list-group-item-action small">Taxonomy permissions</a>
          <a href="{{ route('user.editResearcherAcl', $slug) }}" class="list-group-item list-group-item-action small">Researcher permissions</a>
        </div>
      </section>
    @endauth

  </section>

  @auth
    <ul class="nav gap-2 mt-3 mb-3">
      <li><a href="{{ route('user.edit', $slug) }}" class="btn btn-outline-secondary">Edit</a></li>
      @if(auth()->id() !== $userId)
        <li><a href="{{ route('user.confirmDelete', $slug) }}" class="btn btn-outline-danger">Delete</a></li>
      @endif
      <li><a href="{{ route('user.add') }}" class="btn btn-outline-secondary">Add new</a></li>
      <li><a href="{{ route('user.browse') }}" class="btn btn-outline-secondary">Return to user list</a></li>
    </ul>
  @endauth
@endsection
