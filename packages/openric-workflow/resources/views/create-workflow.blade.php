@extends('theme::layouts.2col')

@section('title', 'Create Workflow')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="fas fa-plus-circle me-2" aria-hidden="true"></i>Create Workflow
        </h1>
        <a href="{{ route('workflow.admin') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Back
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger" role="alert">
            <strong>Please correct the following errors:</strong>
            <ul class="mb-0 mt-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('workflow.admin.store') }}" method="POST" novalidate>
                @csrf

                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                Workflow Name <span class="text-danger" aria-hidden="true">*</span>
                                <span class="visually-hidden">(required)</span>
                            </label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required maxlength="255" aria-describedby="nameHelp">
                            <div id="nameHelp" class="form-text">A descriptive name for this workflow.</div>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="scope_type" class="form-label">
                                Scope <span class="text-danger" aria-hidden="true">*</span>
                                <span class="visually-hidden">(required)</span>
                            </label>
                            <select class="form-select @error('scope_type') is-invalid @enderror" id="scope_type" name="scope_type" required>
                                <option value="global" {{ old('scope_type') === 'global' ? 'selected' : '' }}>Global</option>
                                <option value="repository" {{ old('scope_type') === 'repository' ? 'selected' : '' }}>Repository</option>
                                <option value="collection" {{ old('scope_type') === 'collection' ? 'selected' : '' }}>Collection</option>
                            </select>
                            @error('scope_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="scope_id" class="form-label">Scope ID</label>
                            <input type="number" class="form-control" id="scope_id" name="scope_id" value="{{ old('scope_id') }}" placeholder="Leave empty for global" aria-describedby="scopeIdHelp">
                            <div id="scopeIdHelp" class="form-text">Only required for repository or collection scope.</div>
                        </div>

                        <div class="mb-3">
                            <label for="trigger_event" class="form-label">
                                Trigger Event <span class="text-danger" aria-hidden="true">*</span>
                                <span class="visually-hidden">(required)</span>
                            </label>
                            <select class="form-select @error('trigger_event') is-invalid @enderror" id="trigger_event" name="trigger_event" required>
                                <option value="submit" {{ old('trigger_event') === 'submit' ? 'selected' : '' }}>Submit</option>
                                <option value="publish" {{ old('trigger_event') === 'publish' ? 'selected' : '' }}>Publish</option>
                                <option value="update" {{ old('trigger_event') === 'update' ? 'selected' : '' }}>Update</option>
                                <option value="create" {{ old('trigger_event') === 'create' ? 'selected' : '' }}>Create</option>
                                <option value="manual" {{ old('trigger_event') === 'manual' ? 'selected' : '' }}>Manual</option>
                            </select>
                            @error('trigger_event')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="applies_to" class="form-label">
                                Applies To <span class="text-danger" aria-hidden="true">*</span>
                                <span class="visually-hidden">(required)</span>
                            </label>
                            <select class="form-select @error('applies_to') is-invalid @enderror" id="applies_to" name="applies_to" required>
                                <option value="record_resource" {{ old('applies_to') === 'record_resource' ? 'selected' : '' }}>Record Resource</option>
                                <option value="record_set" {{ old('applies_to') === 'record_set' ? 'selected' : '' }}>Record Set</option>
                                <option value="agent" {{ old('applies_to') === 'agent' ? 'selected' : '' }}>Agent</option>
                                <option value="instantiation" {{ old('applies_to') === 'instantiation' ? 'selected' : '' }}>Instantiation</option>
                            </select>
                            @error('applies_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="auto_archive_days" class="form-label">Auto Archive (days)</label>
                            <input type="number" class="form-control" id="auto_archive_days" name="auto_archive_days" value="{{ old('auto_archive_days') }}" placeholder="Leave empty to disable" min="1">
                        </div>
                    </div>
                </div>

                <fieldset class="mb-3">
                    <legend class="h6">Options</legend>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" {{ old('is_active', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" {{ old('is_default') ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_default">Default Workflow</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="require_all_steps" name="require_all_steps" {{ old('require_all_steps', true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="require_all_steps">Require All Steps</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" id="allow_parallel" name="allow_parallel" {{ old('allow_parallel') ? 'checked' : '' }}>
                                <label class="form-check-label" for="allow_parallel">Allow Parallel Steps</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="notification_enabled" name="notification_enabled" {{ old('notification_enabled', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="notification_enabled">Enable Notifications</label>
                    </div>
                </fieldset>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1" aria-hidden="true"></i>Create Workflow
                    </button>
                    <a href="{{ route('workflow.admin') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
