@extends('theme::layouts.1col')
@section('title', 'Change password')
@section('body-class', 'user password-edit')
@section('content')
<h1>Change password</h1>

@if(session('error'))
  <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<form method="POST" action="{{ route('user.passwordReset') }}">
  @csrf
  <div class="card mb-3">
    <div class="card-header bg-dark text-white"><h5 class="mb-0">Reset password</h5></div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <div class="mb-3">
            <label for="current_password" class="form-label">Current password <span class="text-danger">*</span></label>
            <input type="password" name="current_password" id="current_password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">New password <span class="text-danger">*</span></label>
            <input type="password" name="password" id="password" class="form-control" required>
          </div>
          <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm password <span class="text-danger">*</span></label>
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
          </div>
        </div>
      </div>
    </div>
  </div>
  <ul class="nav gap-2 mb-3">
    <li><a href="{{ url()->previous() }}" class="btn btn-outline-secondary">Cancel</a></li>
    <li><input class="btn btn-outline-success" type="submit" value="Save"></li>
  </ul>
</form>
@endsection
