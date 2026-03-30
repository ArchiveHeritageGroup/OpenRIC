@extends('theme::layouts.1col')
@section('title', 'Edit Policy')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-shield-alt me-3"></i><div><h1 class="mb-0">Edit Policy</h1></div></div>
<form method="POST" action="{{ route('integrity.policies.update', $policy->id ?? 0) }}">@csrf @method('PUT')
<div class="card mb-4"><div class="card-header"><h5 class="mb-0">Edit Policy</h5></div>
<div class="card-body"><div class="row">
    <div class="col-md-6 mb-3"><label for="name" class="form-label">Name</label><input type="text" name="name" id="name" class="form-control" value="{{ $policy->name ?? '' }}" required></div>
    <div class="col-md-6 mb-3"><label for="frequency" class="form-label">Frequency</label><select name="frequency" id="frequency" class="form-select"><option value="daily" {{ ($policy->frequency ?? '') === 'daily' ? 'selected' : '' }}>Daily</option><option value="weekly" {{ ($policy->frequency ?? '') === 'weekly' ? 'selected' : '' }}>Weekly</option><option value="monthly" {{ ($policy->frequency ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option></select></div>
    <div class="col-12 mb-3"><label for="description" class="form-label">Description</label><textarea name="description" id="description" class="form-control" rows="3">{{ $policy->description ?? '' }}</textarea></div>
    <div class="col-md-6 mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ ($policy->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label" for="is_active">Active</label></div></div>
</div></div></div>
<div class="d-flex gap-2"><a href="{{ route('integrity.policies') }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn btn-primary">Save</button></div>
</form>
@endsection
