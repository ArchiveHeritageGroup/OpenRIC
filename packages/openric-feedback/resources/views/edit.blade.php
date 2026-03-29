{{--
  Admin feedback edit form.

  Adapted from Heratio ahg-feedback::edit which displays readonly submission data
  (subject, remarks, type, contact info) and editable admin fields (status,
  admin_notes, completed_at). Uses card-based layout with themed headers.
  OpenRiC replicates the full layout and adds rating display.
--}}
@extends('theme::layouts.1col')

@section('title', 'Edit Feedback')
@section('body-class', 'feedback edit')

@section('content')
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $e)
          <li>{{ $e }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-2x fa-comment-alt me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">Edit Feedback</h1>
      <span class="small text-muted">#{{ $feedback->id }} &middot; {{ $feedback->uuid ?? '' }}</span>
    </div>
  </div>

  <form method="POST" action="{{ route('feedback.update', $feedback->id) }}">
    @csrf

    <div class="row justify-content-center">
      <div class="col-lg-8">

        {{-- Related record — mirrors Heratio's parent_id card --}}
        @if($feedback->url)
        <div class="card shadow-sm mb-3">
          <div class="card-header" style="background:var(--openric-primary, #0d6efd);color:#fff">
            <i class="fas fa-link me-2"></i>Related Record
          </div>
          <div class="card-body">
            <a href="{{ url('/' . $feedback->url) }}" class="btn btn-outline-primary w-100">
              <i class="fas fa-file me-2"></i>View related record: {{ $feedback->url }}
            </a>
          </div>
        </div>
        @endif

        {{-- Feedback Details (readonly) — mirrors Heratio's readonly fields --}}
        <div class="card shadow-sm mb-3">
          <div class="card-header" style="background:var(--openric-primary, #0d6efd);color:#fff">
            <i class="fas fa-comment-alt me-2"></i>Feedback Details
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label fw-semibold">Subject</label>
              <input type="text" class="form-control" value="{{ $feedback->subject }}" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Message</label>
              <textarea class="form-control" rows="5" readonly>{{ $feedback->message }}</textarea>
            </div>
            <div class="row">
              <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Category</label>
                @php
                  $catMap = [
                      'general'    => 'General feedback',
                      'bug'        => 'Bug report',
                      'feature'    => 'Feature request',
                      'content'    => 'Content correction',
                      'compliment' => 'Compliment',
                      'usability'  => 'Usability issue',
                  ];
                @endphp
                <input type="text" class="form-control" value="{{ $catMap[$feedback->category] ?? $feedback->category }}" readonly>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Rating</label>
                <input type="text" class="form-control"
                       value="{{ $feedback->rating ? $feedback->rating . '/5 ' . str_repeat('★', (int) $feedback->rating) . str_repeat('☆', 5 - (int) $feedback->rating) : 'Not rated' }}"
                       readonly>
              </div>
              <div class="col-md-4 mb-3">
                <label class="form-label fw-semibold">Submitted</label>
                <input type="text" class="form-control"
                       value="{{ $feedback->created_at ? \Carbon\Carbon::parse($feedback->created_at)->format('d M Y H:i') : '' }}"
                       readonly>
              </div>
            </div>
            @if($feedback->feed_relationship)
            <div class="mb-0">
              <label class="form-label fw-semibold">Relationship to Archive</label>
              <input type="text" class="form-control" value="{{ $feedback->feed_relationship }}" readonly>
            </div>
            @endif
          </div>
        </div>

        {{-- Contact Info (readonly) — mirrors Heratio's contact card --}}
        <div class="card shadow-sm mb-3">
          <div class="card-header" style="background:var(--openric-primary, #0d6efd);color:#fff">
            <i class="fas fa-address-card me-2"></i>Contact Information
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Name</label>
                <input type="text" class="form-control"
                       value="{{ trim(($feedback->feed_name ?? '') . ' ' . ($feedback->feed_surname ?? '')) ?: ($feedback->user_name ?? 'Anonymous') }}"
                       readonly>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="text" class="form-control"
                       value="{{ $feedback->feed_email ?: ($feedback->user_email ?? '') }}" readonly>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">Phone</label>
                <input type="text" class="form-control" value="{{ $feedback->feed_phone ?? '' }}" readonly>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label fw-semibold">IP Address</label>
                <input type="text" class="form-control" value="{{ $feedback->ip_address ?? 'Unknown' }}" readonly>
              </div>
            </div>
          </div>
        </div>

        {{-- Admin Actions — mirrors Heratio's admin panel with status + notes + completed_at --}}
        <div class="card shadow-sm mb-3">
          <div class="card-header" style="background:var(--openric-primary, #0d6efd);color:#fff">
            <i class="fas fa-cog me-2"></i>Admin Actions
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label for="statusSelect" class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
              <select name="status" id="statusSelect" class="form-select" required>
                <option value="pending" @selected(old('status', $feedback->status) === 'pending')>Pending</option>
                <option value="new" @selected(old('status', $feedback->status) === 'new')>New</option>
                <option value="reviewed" @selected(old('status', $feedback->status) === 'reviewed')>Reviewed</option>
                <option value="completed" @selected(old('status', $feedback->status) === 'completed')>Completed</option>
                <option value="closed" @selected(old('status', $feedback->status) === 'closed')>Closed</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="admin_notes" class="form-label fw-semibold">Admin Notes <span class="badge bg-secondary ms-1">Optional</span></label>
              <textarea name="admin_notes" id="admin_notes" class="form-control" rows="4"
                        placeholder="Internal notes about this feedback..." maxlength="5000">{{ old('admin_notes', $feedback->admin_notes ?? '') }}</textarea>
            </div>
            @if($feedback->reviewed_by || $feedback->reviewer_name)
            <div class="mb-3">
              <label class="form-label fw-semibold">Reviewed By</label>
              <input type="text" class="form-control" value="{{ $feedback->reviewer_name ?? 'User #' . $feedback->reviewed_by }}" readonly>
            </div>
            @endif
            @if($feedback->completed_at)
            <div class="mb-0">
              <label class="form-label fw-semibold">Completed At</label>
              <input type="text" class="form-control"
                     value="{{ \Carbon\Carbon::parse($feedback->completed_at)->format('d M Y H:i') }}" readonly>
              <small class="text-muted">Auto-set when status changes to Completed.</small>
            </div>
            @endif
          </div>
        </div>

        {{-- Actions bar — mirrors Heratio's back/save buttons --}}
        <div class="card shadow-sm">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <a href="{{ route('feedback.browse') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back
              </a>
              <div class="d-flex gap-2">
                <form method="POST" action="{{ route('feedback.destroy', $feedback->id) }}"
                      onsubmit="return confirm('Are you sure you want to delete this feedback? This action cannot be undone.');"
                      class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-outline-danger">
                    <i class="fas fa-trash me-1"></i>Delete
                  </button>
                </form>
                <button type="submit" class="btn btn-success">
                  <i class="fas fa-save me-1"></i>Save
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </form>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const statusSelect = document.getElementById('statusSelect');
      if (!statusSelect) return;

      // Visual feedback when status changes
      statusSelect.addEventListener('change', function () {
        const val = this.value;
        this.className = 'form-select';
        if (val === 'completed') {
          this.classList.add('border-success');
        } else if (val === 'closed') {
          this.classList.add('border-dark');
        } else if (val === 'reviewed') {
          this.classList.add('border-info');
        }
      });
    });
  </script>
@endsection
