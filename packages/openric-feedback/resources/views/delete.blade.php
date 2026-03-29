{{--
  Feedback delete confirmation page.

  Adapted from Heratio ahg-feedback::delete which shows a danger-styled
  confirmation card with the record name and a delete/cancel button pair.
--}}
@extends('theme::layouts.1col')

@section('title', 'Confirm Delete')
@section('body-class', 'feedback delete')

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-6">
      <div class="card border-danger shadow-sm">
        <div class="card-header bg-danger text-white">
          <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
        </div>
        <div class="card-body">
          <p>Are you sure you want to delete feedback
            <strong>#{{ $feedback->id }} &mdash; {{ $feedback->subject ?: '[Untitled]' }}</strong>?
          </p>
          <p class="text-danger mb-4">This action cannot be undone.</p>
          <form method="POST" action="{{ route('feedback.destroy', $feedback->id) }}">
            @csrf
            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-outline-danger">
                <i class="fas fa-trash me-1"></i>Delete
              </button>
              <a href="{{ route('feedback.browse') }}" class="btn btn-outline-secondary">
                <i class="fas fa-times me-1"></i>Cancel
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
