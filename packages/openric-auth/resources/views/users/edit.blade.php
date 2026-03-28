@extends('theme::layouts.2col')
@section('title', 'Edit User')
@section('content')
<h1 class="h3 mb-4"><i class="bi bi-pencil me-2"></i>Edit User: {{ $user->username }}</h1>
@include('theme::partials.alerts')
<form method="POST" action="{{ route('admin.users.update', $user->id) }}">@csrf @method('PUT')
    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username', $user->username) }}" required>
                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Password <span class="text-muted">(leave blank to keep current)</span></label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                </div>
            </div>
            <div class="mb-3">
                <label for="display_name" class="form-label">Display Name</label>
                <input type="text" class="form-control" id="display_name" name="display_name" value="{{ old('display_name', $user->display_name) }}">
            </div>
            <div class="mb-3">
                <label class="form-label">Roles</label>
                @foreach($roles as $role)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}" {{ in_array($role->id, $userRoleIds) ? 'checked' : '' }}>
                        <label class="form-check-label" for="role_{{ $role->id }}">{{ $role->label }}</label>
                    </div>
                @endforeach
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="active" value="1" id="active" {{ $user->active ? 'checked' : '' }}>
                <label class="form-check-label" for="active">Active</label>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-check me-1"></i>Save Changes</button>
    <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-secondary">Cancel</a>
</form>
@endsection
