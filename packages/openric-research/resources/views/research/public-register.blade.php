@extends('theme::layouts.1col')
@section('title', 'Public Researcher Registration')
@section('content')
<div class="container py-4" style="max-width:700px;">
    <h2 class="mb-4">Create a Researcher Account</h2>
    @if(session('error'))<div class="alert alert-danger">{!! session('error') !!}</div>@endif

    <form method="POST" action="{{ route('research.publicRegister') }}">
        @csrf
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Account Credentials</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Username *</label><input type="text" name="username" class="form-control" required minlength="3" value="{{ old('username') }}"></div>
                    <div class="col-md-6"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required value="{{ old('email') }}"></div>
                    <div class="col-md-6"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" required minlength="8"></div>
                    <div class="col-md-6"><label class="form-label">Confirm Password *</label><input type="password" name="confirm_password" class="form-control" required></div>
                </div>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Personal Information</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-2"><label class="form-label">Title</label><select name="title" class="form-select"><option value="">--</option><option>Mr</option><option>Mrs</option><option>Ms</option><option>Dr</option><option>Prof</option></select></div>
                    <div class="col-md-5"><label class="form-label">First Name *</label><input type="text" name="first_name" class="form-control" required value="{{ old('first_name') }}"></div>
                    <div class="col-md-5"><label class="form-label">Last Name *</label><input type="text" name="last_name" class="form-control" required value="{{ old('last_name') }}"></div>
                    <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control" value="{{ old('phone') }}"></div>
                    <div class="col-md-6"><label class="form-label">Affiliation</label><select name="affiliation_type" class="form-select"><option value="independent">Independent</option><option value="academic">Academic</option><option value="government">Government</option><option value="student">Student</option></select></div>
                    <div class="col-md-6"><label class="form-label">Institution</label><input type="text" name="institution" class="form-control" value="{{ old('institution') }}"></div>
                    <div class="col-md-6"><label class="form-label">ORCID</label><input type="text" name="orcid_id" class="form-control" value="{{ old('orcid_id') }}"></div>
                </div>
                <div class="mt-3"><label class="form-label">Research Interests</label><textarea name="research_interests" class="form-control" rows="3">{{ old('research_interests') }}</textarea></div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Register</button>
        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-lg ms-2">Already have an account?</a>
    </form>
</div>
@endsection
