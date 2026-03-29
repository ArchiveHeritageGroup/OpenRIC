{{--
  Feedback submission success page.

  Adapted from Heratio ahg-feedback::submit which shows a simple thank-you
  card with a check icon and back button.
--}}
@extends('theme::layouts.1col')

@section('title', 'Feedback Submitted')
@section('body-class', 'feedback success')

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card shadow-sm">
        <div class="card-body text-center py-5">
          <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
          <h3>Feedback Submitted</h3>
          <p class="text-muted mb-4">Thank you for your feedback. We will review it shortly.</p>
          <div class="d-flex justify-content-center gap-2">
            <a href="{{ route('feedback.general') }}" class="btn btn-outline-primary">
              <i class="fas fa-plus me-1"></i>Submit Another
            </a>
            <a href="{{ url('/') }}" class="btn btn-outline-secondary">
              <i class="fas fa-home me-1"></i>Home
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
