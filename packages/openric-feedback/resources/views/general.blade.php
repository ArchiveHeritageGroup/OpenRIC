{{--
  Public feedback submission form.

  Adapted from Heratio ahg-feedback::general which renders a two-card form
  (feedback content + contact details) with feedback types loaded from
  taxonomy or hardcoded defaults. OpenRiC uses categories from the service
  and adds a rating field.
--}}
@extends('theme::layouts.1col')

@section('title', 'General Feedback')
@section('body-class', 'feedback')

@section('content')
<div class="d-flex align-items-center mb-3">
  <i class="fas fa-comments fa-2x text-primary me-3" aria-hidden="true"></i>
  <div>
    <h1 class="h3 mb-0">General Feedback</h1>
    <p class="text-muted mb-0">Share your feedback, suggestions, or report issues</p>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $e)
        <li>{{ $e }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form method="POST" action="{{ route('feedback.general') }}">
  @csrf

  @if($slug)
    <input type="hidden" name="url" value="{{ $slug }}">
  @endif

  <div class="row justify-content-center">
    <div class="col-lg-8">

      {{-- Related record banner (when submitting feedback about a specific record) --}}
      @if($slug)
      <div class="alert alert-info mb-3">
        <i class="fas fa-link me-2"></i>
        You are submitting feedback about: <strong>{{ $slug }}</strong>
      </div>
      @endif

      {{-- Feedback Type & Content --}}
      <div class="card shadow-sm mb-3">
        <div class="card-header" style="background:var(--openric-primary, #0d6efd);color:#fff">
          <i class="fas fa-comment-alt me-2"></i>Your Feedback
        </div>
        <div class="card-body">
          <div class="mb-3">
            <label for="subject" class="form-label fw-semibold">Subject <span class="text-danger">*</span></label>
            <input type="text" name="subject" id="subject" class="form-control @error('subject') is-invalid @enderror"
                   required value="{{ old('subject') }}" placeholder="Brief subject of your feedback" maxlength="1024">
            @error('subject')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-3">
            <label for="category" class="form-label fw-semibold">Feedback Type <span class="text-danger">*</span></label>
            <select name="category" id="category" class="form-select @error('category') is-invalid @enderror" required>
              <option value="">-- Select --</option>
              @foreach($categories as $cat)
                <option value="{{ $cat['id'] }}" @selected(old('category') === $cat['id'])>{{ $cat['name'] }}</option>
              @endforeach
            </select>
            @error('category')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="mb-3">
            <label for="message" class="form-label fw-semibold">Your Feedback / Comments <span class="text-danger">*</span></label>
            <textarea name="message" id="message" class="form-control @error('message') is-invalid @enderror"
                      rows="6" required placeholder="Please provide details about your feedback, suggestion, or issue..."
                      maxlength="10000">{{ old('message') }}</textarea>
            @error('message')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="rating" class="form-label fw-semibold">Rating <span class="badge bg-secondary ms-1">Optional</span></label>
              <select name="rating" id="rating" class="form-select">
                <option value="">-- No rating --</option>
                @for($i = 5; $i >= 1; $i--)
                  <option value="{{ $i }}" @selected(old('rating') == $i)>{{ $i }} {{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }}</option>
                @endfor
              </select>
            </div>
            <div class="col-md-6 mb-3">
              <label for="feed_relationship" class="form-label fw-semibold">Your Relationship to the Archive <span class="badge bg-secondary ms-1">Optional</span></label>
              <input type="text" name="feed_relationship" id="feed_relationship" class="form-control"
                     value="{{ old('feed_relationship') }}" placeholder="e.g., Researcher, visitor, donor...">
            </div>
          </div>
        </div>
      </div>

      {{-- Contact Details --}}
      <div class="card shadow-sm mb-3">
        <div class="card-header" style="background:var(--openric-primary, #0d6efd);color:#fff">
          <i class="fas fa-address-card me-2"></i>Your Contact Details
        </div>
        <div class="card-body">
          <p class="text-muted small mb-3">Please provide your contact details so we can follow up if needed.</p>
          <div class="row g-3">
            <div class="col-md-6">
              <label for="feed_name" class="form-label fw-semibold">Name <span class="text-danger">*</span></label>
              <input type="text" name="feed_name" id="feed_name"
                     class="form-control @error('feed_name') is-invalid @enderror"
                     required value="{{ old('feed_name', auth()->user()->name ?? '') }}"
                     placeholder="Your first name" maxlength="100">
              @error('feed_name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label for="feed_surname" class="form-label fw-semibold">Surname <span class="text-danger">*</span></label>
              <input type="text" name="feed_surname" id="feed_surname"
                     class="form-control @error('feed_surname') is-invalid @enderror"
                     required value="{{ old('feed_surname') }}" placeholder="Your surname" maxlength="100">
              @error('feed_surname')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label for="feed_phone" class="form-label fw-semibold">Phone Number <span class="badge bg-secondary ms-1">Optional</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                <input type="tel" name="feed_phone" id="feed_phone" class="form-control"
                       value="{{ old('feed_phone') }}" placeholder="Contact number" maxlength="50">
              </div>
            </div>
            <div class="col-md-6">
              <label for="feed_email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                <input type="email" name="feed_email" id="feed_email"
                       class="form-control @error('feed_email') is-invalid @enderror"
                       required value="{{ old('feed_email', auth()->user()->email ?? '') }}"
                       placeholder="your@email.com" maxlength="255">
                @error('feed_email')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Submit --}}
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <a href="{{ url('/') }}" class="btn btn-outline-secondary">
              <i class="fas fa-times me-1"></i>Cancel
            </a>
            <button type="submit" class="btn btn-success btn-lg">
              <i class="fas fa-paper-plane me-1"></i>Submit Feedback
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</form>
@endsection
