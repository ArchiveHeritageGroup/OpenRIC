@extends('theme::layouts.1col')

@section('title', 'Compare Duplicates')
@section('body-class', 'admin dedupe compare')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-columns me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">Compare Duplicates</h1>
      <span class="small text-muted">
        Similarity score:
        @php
          $scoreVal = round(($score ?? 0) * 100);
          $badgeClass = $scoreVal >= 90 ? 'bg-danger' : ($scoreVal >= 75 ? 'bg-warning text-dark' : 'bg-info text-dark');
        @endphp
        <span class="badge {{ $badgeClass }}">{{ $scoreVal }}%</span>
        &middot; Status: {{ ucfirst(str_replace('_', ' ', $candidate['status'] ?? '')) }}
      </span>
    </div>
    <div class="ms-auto d-flex gap-2">
      @if(($candidate['status'] ?? '') !== 'merged')
        <a href="{{ route('dedupe.merge', $candidate['id']) }}" class="btn atom-btn-outline-success">
          <i class="fas fa-compress-arrows-alt me-1"></i> Merge Records
        </a>
      @endif
      <a href="{{ route('dedupe.records') }}" class="btn atom-btn-white">
        <i class="fas fa-arrow-left me-1"></i> Back
      </a>
    </div>
  </div>

  {{-- Side-by-side headers --}}
  <div class="row mb-3">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff">
          <strong>Entity A</strong>
        </div>
        <div class="card-body py-2">
          <strong>{{ $entityA['rico:title'] ?? $entityA['title'] ?? '[Untitled]' }}</strong>
          <br><small class="text-muted text-break">IRI: {{ $entityA['iri'] ?? '' }}</small>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff">
          <strong>Entity B</strong>
        </div>
        <div class="card-body py-2">
          <strong>{{ $entityB['rico:title'] ?? $entityB['title'] ?? '[Untitled]' }}</strong>
          <br><small class="text-muted text-break">IRI: {{ $entityB['iri'] ?? '' }}</small>
        </div>
      </div>
    </div>
  </div>

  {{-- Field comparison --}}
  <div class="card mb-4">
    <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Field Comparison</strong></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <thead>
            <tr>
              <th style="width: 180px;">Field</th>
              <th>Entity A</th>
              <th>Entity B</th>
              <th style="width: 80px;">Match</th>
            </tr>
          </thead>
          <tbody>
            @foreach($comparison as $field)
              @php
                $bgClass = '';
                if ($field['a'] !== '' || $field['b'] !== '') {
                    $bgClass = $field['match'] ? 'table-success' : 'table-warning';
                }
              @endphp
              <tr class="{{ $bgClass }}">
                <td><strong>{{ $field['label'] }}</strong></td>
                <td>{!! nl2br(e($field['a'] ?: '-')) !!}</td>
                <td>{!! nl2br(e($field['b'] ?: '-')) !!}</td>
                <td class="text-center">
                  @if($field['match'] && ($field['a'] !== '' || $field['b'] !== ''))
                    <i class="fas fa-check text-success"></i>
                  @elseif($field['a'] !== '' || $field['b'] !== '')
                    <i class="fas fa-times text-danger"></i>
                  @else
                    <span class="text-muted">-</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- Detection Info --}}
  <div class="alert alert-info mb-4">
    <div class="row">
      <div class="col-md-4">
        <strong>Similarity Score:</strong>
        <span class="badge {{ $badgeClass }} fs-6">{{ number_format($scoreVal, 1) }}%</span>
      </div>
      <div class="col-md-4">
        <strong>Entity Type:</strong>
        {{ $candidate['entity_type'] ?? 'Record' }}
      </div>
      <div class="col-md-4">
        <strong>Detected:</strong>
        {{ isset($candidate['created_at']) ? \Carbon\Carbon::parse($candidate['created_at'])->format('M j, Y H:i') : '-' }}
      </div>
    </div>
  </div>

  {{-- Legend --}}
  <div class="alert alert-secondary mb-4">
    <i class="fas fa-info-circle me-2"></i>
    <strong>Legend:</strong>
    <span class="badge bg-success">Green rows</span> indicate matching values.
    <span class="badge bg-warning text-dark">Yellow rows</span> indicate differing values between entities.
  </div>

  {{-- Actions --}}
  @if(($candidate['status'] ?? '') === 'pending')
    <div class="d-flex gap-2">
      <a href="{{ route('dedupe.merge', $candidate['id']) }}" class="btn atom-btn-outline-success">
        <i class="fas fa-compress-arrows-alt me-1"></i> Merge Records
      </a>
      <button type="button" class="btn atom-btn-white" id="btn-dismiss-duplicate">
        <i class="fas fa-times me-1"></i> Not a Duplicate
      </button>
    </div>
  @else
    <div class="alert alert-info">
      This duplicate pair has been <strong>{{ str_replace('_', ' ', $candidate['status'] ?? '') }}</strong>.
      @if(!empty($candidate['resolved_at']))
        Reviewed at {{ \Carbon\Carbon::parse($candidate['resolved_at'])->format('Y-m-d H:i') }}.
      @endif
    </div>
  @endif
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dismissBtn = document.getElementById('btn-dismiss-duplicate');
    if (dismissBtn) {
        dismissBtn.addEventListener('click', function () {
            if (!confirm('Mark this pair as not duplicate?')) return;
            fetch('{{ route("dedupe.resolve", $candidate["id"]) }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({resolution: 'not_duplicate'}),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    window.location.href = '{{ route("dedupe.records") }}';
                }
            });
        });
    }
});
</script>
@endpush
