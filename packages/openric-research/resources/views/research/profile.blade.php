@extends('theme::layouts.1col')
@section('title', 'Researcher Profile')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Researcher Profile</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{!! session('error') !!}</div>@endif

        <form method="POST" action="{{ route('research.profile') }}">
            @csrf
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Personal Details</h5>
                    <span class="badge bg-{{ $researcher->status === 'approved' ? 'success' : ($researcher->status === 'pending' ? 'warning' : 'secondary') }}">{{ ucfirst($researcher->status) }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2"><label class="form-label">Title</label><select name="title" class="form-select"><option value="">--</option>@foreach(['Mr','Mrs','Ms','Dr','Prof'] as $t)<option {{ ($researcher->title ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></div>
                        <div class="col-md-5"><label class="form-label">First Name</label><input type="text" name="first_name" class="form-control" value="{{ $researcher->first_name }}"></div>
                        <div class="col-md-5"><label class="form-label">Last Name</label><input type="text" name="last_name" class="form-control" value="{{ $researcher->last_name }}"></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" value="{{ $researcher->email }}" disabled></div>
                        <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="{{ $researcher->phone }}"></div>
                        <div class="col-md-4"><label class="form-label">Affiliation</label><select name="affiliation_type" class="form-select">@foreach(['independent','academic','government','professional','student'] as $a)<option value="{{ $a }}" {{ ($researcher->affiliation_type ?? '') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label class="form-label">Institution</label><input type="text" name="institution" class="form-control" value="{{ $researcher->institution }}"></div>
                        <div class="col-md-4"><label class="form-label">Department</label><input type="text" name="department" class="form-control" value="{{ $researcher->department }}"></div>
                        <div class="col-md-4"><label class="form-label">Position</label><input type="text" name="position" class="form-control" value="{{ $researcher->position }}"></div>
                        <div class="col-md-4"><label class="form-label">ORCID</label><input type="text" name="orcid_id" class="form-control" value="{{ $researcher->orcid_id }}"></div>
                        <div class="col-12"><label class="form-label">Research Interests</label><textarea name="research_interests" class="form-control" rows="3">{{ $researcher->research_interests }}</textarea></div>
                        <div class="col-12"><label class="form-label">Current Project</label><textarea name="current_project" class="form-control" rows="2">{{ $researcher->current_project }}</textarea></div>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Profile</button>
            <a href="{{ route('research.apiKeys') }}" class="btn btn-outline-secondary ms-2">Manage API Keys</a>
            @if(in_array($researcher->status, ['expired','approved']))
                <a href="{{ route('research.renewal') }}" class="btn btn-outline-warning ms-2">Renewal</a>
            @endif
        </form>

        {{-- Summary --}}
        <div class="row g-3 mt-4">
            <div class="col-md-4">
                <div class="card"><div class="card-body text-center"><h4>{{ count($bookings ?? []) }}</h4><p class="mb-0">Bookings</p></div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body text-center"><h4>{{ count($collections ?? []) }}</h4><p class="mb-0">Collections</p></div></div>
            </div>
            <div class="col-md-4">
                <div class="card"><div class="card-body text-center"><h4>{{ count($savedSearches ?? []) }}</h4><p class="mb-0">Saved Searches</p></div></div>
            </div>
        </div>
    </div>
</div>
@endsection
