@extends('theme::layouts.1col')
@section('title', 'Reset password')
@section('body-class', 'user password-reset-confirm')
@section('content')
<h1>Reset password</h1>
<div class="card"><div class="card-body">
  <p>A password reset link has been sent. Please check your email.</p>
  <p>Token: <code>{{ $token ?? '' }}</code></p>
  <a href="{{ route('login') }}" class="btn btn-outline-secondary">Back to login</a>
</div></div>
@endsection
