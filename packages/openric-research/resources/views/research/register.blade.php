@extends('theme::layouts.1col')
@section('title', 'Researcher Registration')
@section('content')
<div class="d-flex">
    @include('research::research._sidebar')
    <div class="flex-grow-1 p-4">
        <h2 class="mb-4">Researcher Registration</h2>
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{!! session('error') !!}</div>@endif

        @if(isset($existingResearcher) && $existingResearcher)
            <div class="alert alert-warning">Your previous registration was not approved. You may re-apply below.</div>
        @endif

        <form method="POST" action="{{ route('researcher.register') }}">
            @csrf
            <div class="card">
                <div class="card-header"><h5 class="mb-0">Personal Information</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label">Title</label>
                            <select name="title" class="form-select">
                                <option value="">--</option>
                                @foreach(['Mr', 'Mrs', 'Ms', 'Dr', 'Prof'] as $t)
                                    <option value="{{ $t }}" {{ old('title', $existingResearcher->title ?? '') === $t ? 'selected' : '' }}>{{ $t }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">First Name *</label>
                            <input type="text" name="first_name" class="form-control" required value="{{ old('first_name', $existingResearcher->first_name ?? $user->name ?? '') }}">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required value="{{ old('last_name', $existingResearcher->last_name ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" name="email" class="form-control" required value="{{ old('email', $existingResearcher->email ?? $user->email ?? '') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $existingResearcher->phone ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h5 class="mb-0">Affiliation</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Affiliation Type</label>
                            <select name="affiliation_type" class="form-select">
                                @foreach(['independent' => 'Independent', 'academic' => 'Academic', 'government' => 'Government', 'professional' => 'Professional', 'student' => 'Student'] as $k => $v)
                                    <option value="{{ $k }}" {{ old('affiliation_type', $existingResearcher->affiliation_type ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Institution</label>
                            <input type="text" name="institution" class="form-control" value="{{ old('institution', $existingResearcher->institution ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" class="form-control" value="{{ old('department', $existingResearcher->department ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Position</label>
                            <input type="text" name="position" class="form-control" value="{{ old('position', $existingResearcher->position ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-control" value="{{ old('student_id', $existingResearcher->student_id ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ORCID</label>
                            <input type="text" name="orcid_id" class="form-control" placeholder="0000-0000-0000-0000" value="{{ old('orcid_id', $existingResearcher->orcid_id ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h5 class="mb-0">Identification</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">ID Type</label>
                            <select name="id_type" class="form-select">
                                <option value="">-- Select --</option>
                                @foreach(['passport' => 'Passport', 'national_id' => 'National ID', 'drivers_license' => 'Driver\'s License'] as $k => $v)
                                    <option value="{{ $k }}" {{ old('id_type', $existingResearcher->id_type ?? '') === $k ? 'selected' : '' }}>{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ID Number</label>
                            <input type="text" name="id_number" class="form-control" value="{{ old('id_number', $existingResearcher->id_number ?? '') }}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header"><h5 class="mb-0">Research Details</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Research Interests</label>
                        <textarea name="research_interests" class="form-control" rows="3">{{ old('research_interests', $existingResearcher->research_interests ?? '') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Project</label>
                        <textarea name="current_project" class="form-control" rows="3">{{ old('current_project', $existingResearcher->current_project ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary btn-lg">Submit Registration</button>
            </div>
        </form>
    </div>
</div>
@endsection
