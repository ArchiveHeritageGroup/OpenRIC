{{--
  Read-only feedback detail view.

  Adapted from Heratio ahg-feedback::view which displays all feedback fields
  in a definition list within a card. OpenRiC expands the layout with separate
  cards for submission details, contact info, and admin status.
--}}
@extends('theme::layouts.1col')

@section('title', 'Feedback Details')
@section('body-class', 'feedback show')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-eye me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">Feedback Details</h1>
      <span class="small text-muted">#{{ $feedback->id }}</span>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-8">

      {{-- Submission Details --}}
      <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold" style="background:var(--openric-primary, #0d6efd);color:#fff">
          <i class="fas fa-comment-alt me-2"></i>Submission Details
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">Subject</dt>
            <dd class="col-sm-8">{{ $feedback->subject ?: '-' }}</dd>

            <dt class="col-sm-4">Category</dt>
            <dd class="col-sm-8">
              @php
                $catMap = [
                    'general'    => ['label' => 'General feedback',  'class' => 'bg-secondary'],
                    'bug'        => ['label' => 'Bug report',        'class' => 'bg-danger'],
                    'feature'    => ['label' => 'Feature request',   'class' => 'bg-info text-dark'],
                    'content'    => ['label' => 'Content correction', 'class' => 'bg-primary'],
                    'compliment' => ['label' => 'Compliment',        'class' => 'bg-success'],
                    'usability'  => ['label' => 'Usability issue',   'class' => 'bg-warning text-dark'],
                ];
                $catInfo = $catMap[$feedback->category] ?? ['label' => $feedback->category, 'class' => 'bg-secondary'];
              @endphp
              <span class="badge {{ $catInfo['class'] }}">{{ $catInfo['label'] }}</span>
            </dd>

            <dt class="col-sm-4">Message</dt>
            <dd class="col-sm-8" style="white-space:pre-wrap">{{ $feedback->message ?: '-' }}</dd>

            @if($feedback->rating)
            <dt class="col-sm-4">Rating</dt>
            <dd class="col-sm-8">
              <span class="text-warning">{{ str_repeat('★', (int) $feedback->rating) }}{{ str_repeat('☆', 5 - (int) $feedback->rating) }}</span>
              ({{ $feedback->rating }}/5)
            </dd>
            @endif

            @if($feedback->feed_relationship)
            <dt class="col-sm-4">Relationship</dt>
            <dd class="col-sm-8">{{ $feedback->feed_relationship }}</dd>
            @endif

            @if($feedback->url)
            <dt class="col-sm-4">Related Record</dt>
            <dd class="col-sm-8">
              <a href="{{ url('/' . $feedback->url) }}">
                <i class="fas fa-link me-1"></i>{{ $feedback->url }}
              </a>
            </dd>
            @endif

            <dt class="col-sm-4">Submitted</dt>
            <dd class="col-sm-8">
              {{ $feedback->created_at ? \Carbon\Carbon::parse($feedback->created_at)->format('d M Y H:i') : '-' }}
            </dd>
          </dl>
        </div>
      </div>

      {{-- Contact Information --}}
      <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold" style="background:var(--openric-primary, #0d6efd);color:#fff">
          <i class="fas fa-address-card me-2"></i>Contact Information
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">Name</dt>
            <dd class="col-sm-8">
              {{ trim(($feedback->feed_name ?? '') . ' ' . ($feedback->feed_surname ?? '')) ?: ($feedback->user_name ?? 'Anonymous') }}
            </dd>

            <dt class="col-sm-4">Email</dt>
            <dd class="col-sm-8">{{ $feedback->feed_email ?: ($feedback->user_email ?? '-') }}</dd>

            @if($feedback->feed_phone)
            <dt class="col-sm-4">Phone</dt>
            <dd class="col-sm-8">{{ $feedback->feed_phone }}</dd>
            @endif
          </dl>
        </div>
      </div>

      {{-- Status & Admin --}}
      <div class="card shadow-sm mb-3">
        <div class="card-header fw-semibold" style="background:var(--openric-primary, #0d6efd);color:#fff">
          <i class="fas fa-cog me-2"></i>Status
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">Status</dt>
            <dd class="col-sm-8">
              @php
                $statusBadge = match ($feedback->status) {
                    'completed' => '<span class="badge bg-success">Completed</span>',
                    'reviewed'  => '<span class="badge bg-info text-dark">Reviewed</span>',
                    'closed'    => '<span class="badge bg-dark">Closed</span>',
                    'new'       => '<span class="badge bg-primary">New</span>',
                    default     => '<span class="badge bg-warning text-dark">Pending</span>',
                };
              @endphp
              {!! $statusBadge !!}
            </dd>

            @if($feedback->admin_notes)
            <dt class="col-sm-4">Admin Notes</dt>
            <dd class="col-sm-8" style="white-space:pre-wrap">{{ $feedback->admin_notes }}</dd>
            @endif

            @if($feedback->reviewer_name)
            <dt class="col-sm-4">Reviewed By</dt>
            <dd class="col-sm-8">{{ $feedback->reviewer_name }}</dd>
            @endif

            @if($feedback->completed_at)
            <dt class="col-sm-4">Completed At</dt>
            <dd class="col-sm-8">{{ \Carbon\Carbon::parse($feedback->completed_at)->format('d M Y H:i') }}</dd>
            @endif

            <dt class="col-sm-4">Last Updated</dt>
            <dd class="col-sm-8">{{ $feedback->updated_at ? \Carbon\Carbon::parse($feedback->updated_at)->format('d M Y H:i') : '-' }}</dd>
          </dl>
        </div>
      </div>
    </div>

    {{-- Sidebar actions --}}
    <div class="col-lg-4">
      <div class="d-grid gap-2">
        <a href="{{ route('feedback.edit', $feedback->id) }}" class="btn btn-outline-primary">
          <i class="fas fa-edit me-1"></i>Edit
        </a>
        <a href="{{ route('feedback.browse') }}" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left me-1"></i>Back to List
        </a>
        <form method="POST" action="{{ route('feedback.destroy', $feedback->id) }}"
              onsubmit="return confirm('Are you sure you want to delete this feedback?');">
          @csrf
          <button type="submit" class="btn btn-outline-danger w-100">
            <i class="fas fa-trash me-1"></i>Delete
          </button>
        </form>
      </div>
    </div>
  </div>
@endsection
