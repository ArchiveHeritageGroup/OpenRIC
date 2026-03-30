@extends('theme::layouts.1col')

@section('title', 'RAD Editor')

@section('content')
<h1>RAD Editor</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST">
    @csrf
    
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Basic Information</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" value="{{ $io->title ?? '' }}" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label">Identifier</label>
                    <input type="text" name="identifier" class="form-control" value="{{ $io->identifier ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Level of Description</label>
                    <select name="level_of_description_id" class="form-select">
                        <option value="">Select...</option>
                        @foreach($levels ?? [] as $level)
                            <option value="{{ $level->id }}" {{ ($io->level_of_description_id ?? '') == $level->id ? 'selected' : '' }}>
                                {{ $level->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Alternate Title</label>
                    <input type="text" name="alternate_title" class="form-control" value="{{ $io->alternate_title ?? '' }}">
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Content Description</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Extent and Medium</label>
                    <textarea name="extent_and_medium" class="form-control" rows="2">{{ $io->extent_and_medium ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Archival History</label>
                    <textarea name="archival_history" class="form-control" rows="3">{{ $io->archival_history ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Acquisition</label>
                    <textarea name="acquisition" class="form-control" rows="2">{{ $io->acquisition ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Scope and Content</label>
                    <textarea name="scope_and_content" class="form-control" rows="4">{{ $io->scope_and_content ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Appraisal</label>
                    <textarea name="appraisal" class="form-control" rows="2">{{ $io->appraisal ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Accruals</label>
                    <textarea name="accruals" class="form-control" rows="2">{{ $io->accruals ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Arrangement</label>
                    <textarea name="arrangement" class="form-control" rows="3">{{ $io->arrangement ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Conditions of Access</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Conditions Governing Access</label>
                    <textarea name="access_conditions" class="form-control" rows="3">{{ $io->access_conditions ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Conditions Governing Reproduction</label>
                    <textarea name="reproduction_conditions" class="form-control" rows="2">{{ $io->reproduction_conditions ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Physical Characteristics</label>
                    <textarea name="physical_characteristics" class="form-control" rows="2">{{ $io->physical_characteristics ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-warning">
            <h5 class="mb-0">Allied Materials</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Location of Originals</label>
                    <textarea name="location_of_originals" class="form-control" rows="2">{{ $io->location_of_originals ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Location of Copies</label>
                    <textarea name="location_of_copies" class="form-control" rows="2">{{ $io->location_of_copies ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Related Units of Description</label>
                    <textarea name="related_units_of_description" class="form-control" rows="3">{{ $io->related_units_of_description ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Notes</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Institution Responsible Identifier</label>
                    <input type="text" name="institution_responsible_identifier" class="form-control" value="{{ $io->institution_responsible_identifier ?? '' }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Rules</label>
                    <input type="text" name="rules" class="form-control" value="{{ $io->rules ?? 'RAD version Jul2008' }}">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Sources</label>
                    <textarea name="sources" class="form-control" rows="3">{{ $io->sources ?? '' }}</textarea>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label">Revision History</label>
                    <textarea name="revision_history" class="form-control" rows="3">{{ $io->revision_history ?? '' }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="text-end">
        <a href="{{ url()->previous() }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save RAD Description</button>
    </div>
</form>
@endsection
