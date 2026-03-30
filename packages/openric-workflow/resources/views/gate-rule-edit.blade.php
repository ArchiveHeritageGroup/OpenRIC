@extends('theme::layouts.1col')
@section('title', 'Edit Gate Rule')
@section('content')
<div class="d-flex align-items-center mb-3"><i class="fas fa-3x fa-shield-alt me-3"></i><div><h1 class="mb-0">Edit Gate Rule</h1></div></div>
<div class="card"><div class="card-header fw-semibold"><i class="fas fa-edit me-2"></i>Edit Gate Rule</div>
<div class="card-body"><form method="POST" action="{{ $formAction ?? '#' }}">@csrf
    <div class="mb-3"><label class="form-label">Rule Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="name" required></div>
    <div class="mb-3"><label class="form-label">Condition</label><textarea class="form-control" name="condition" rows="2"></textarea></div>
    <div class="mb-3"><label class="form-label">Priority</label><input type="number" class="form-control" name="priority"></div>
    <div class="d-flex gap-2 mt-3"><button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save</button><a href="{{ url()->previous() }}" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Cancel</a></div>
</form></div></div>
@endsection
