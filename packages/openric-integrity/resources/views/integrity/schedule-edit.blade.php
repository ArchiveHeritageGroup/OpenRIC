@extends('theme::layouts.1col')
@section('title', 'Edit Schedule')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-shield-alt me-3"></i><div><h1 class="mb-0">Edit Schedule</h1></div></div>
<form method="POST" action="{{ route('integrity.schedules.update', $schedule->id ?? 0) }}">@csrf @method('PUT')
<div class="card mb-4"><div class="card-header"><h5 class="mb-0">Edit Schedule</h5></div>
<div class="card-body"><div class="row">
    <div class="col-md-6 mb-3"><label for="name" class="form-label">Name</label><input type="text" name="name" id="name" class="form-control" value="{{ $schedule->name ?? '' }}" required></div>
    <div class="col-md-6 mb-3"><label for="cron_expression" class="form-label">Cron Expression</label><input type="text" name="cron_expression" id="cron_expression" class="form-control" value="{{ $schedule->cron_expression ?? '' }}" placeholder="0 2 * * *"></div>
    <div class="col-md-6 mb-3"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ ($schedule->is_active ?? true) ? 'checked' : '' }}><label class="form-check-label" for="is_active">Active</label></div></div>
</div></div></div>
<div class="d-flex gap-2"><a href="{{ route('integrity.schedules') }}" class="btn btn-outline-secondary">Cancel</a><button type="submit" class="btn btn-primary">Save</button></div>
</form>
@endsection
