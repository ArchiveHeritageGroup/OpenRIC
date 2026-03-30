@extends('theme::layouts.1col')

@section('title', 'Duplicate Detection')
@section('body-class', 'admin dedupe')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-clone me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">Duplicate Detection</h1>
      <span class="small text-muted">Dashboard</span>
    </div>
    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('dedupe.scan') }}" class="btn atom-btn-outline-success">
        <i class="fas fa-search me-1"></i> New Scan
      </a>
      <a href="{{ route('dedupe.rules') }}" class="btn atom-btn-white">
        <i class="fas fa-cog me-1"></i> Rules
      </a>
      <a href="{{ route('dedupe.records') }}" class="btn atom-btn-white">
        <i class="fas fa-list me-1"></i> Browse All
      </a>
    </div>
  </div>

  {{-- Stat cards --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card text-center">
        <div class="card-body py-2">
          <div class="fs-3 fw-bold">{{ number_format($stats['totalDetected'] ?? 0) }}</div>
          <div class="small text-muted">Total Detected</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card text-center border-warning">
        <div class="card-body py-2">
          <div class="fs-3 fw-bold text-warning">{{ number_format($stats['pending'] ?? 0) }}</div>
          <div class="small text-muted">Pending Review</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card text-center border-danger">
        <div class="card-body py-2">
          <div class="fs-3 fw-bold text-danger">{{ number_format($stats['confirmed'] ?? 0) }}</div>
          <div class="small text-muted">Confirmed</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card text-center border-success">
        <div class="card-body py-2">
          <div class="fs-3 fw-bold text-success">{{ number_format($stats['merged'] ?? 0) }}</div>
          <div class="small text-muted">Merged</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card text-center border-secondary">
        <div class="card-body py-2">
          <div class="fs-3 fw-bold text-secondary">{{ number_format($stats['notDuplicate'] ?? 0) }}</div>
          <div class="small text-muted">Not Duplicate</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
      <div class="card text-center border-primary">
        <div class="card-body py-2">
          <div class="fs-3 fw-bold text-primary">{{ number_format($stats['activeRules'] ?? 0) }}</div>
          <div class="small text-muted">Active Rules</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    {{-- Pending Review --}}
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header d-flex align-items-center" style="background:var(--bs-primary, #0d6efd);color:#fff">
          <strong>Pending Review</strong>
          <span class="badge bg-warning text-dark ms-2">{{ number_format($stats['pending'] ?? 0) }}</span>
          <a href="{{ route('dedupe.records', ['status' => 'pending']) }}" class="ms-auto small text-white">View all</a>
        </div>
        <div class="card-body p-0">
          @if(empty($topPending))
            <div class="p-3 text-muted">No pending duplicates.</div>
          @else
            <div class="table-responsive">
              <table class="table table-bordered table-striped mb-0">
                <thead>
                  <tr>
                    <th style="width: 70px;">Score</th>
                    <th>Entity A</th>
                    <th>Entity B</th>
                    <th>Type</th>
                    <th style="width: 140px;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($topPending as $dup)
                    <tr id="dup-row-{{ $dup['id'] }}">
                      <td class="text-center">
                        @php
                          $score = round(($dup['similarity_score'] ?? 0) * 100);
                          $badgeClass = $score >= 90 ? 'bg-danger' : ($score >= 75 ? 'bg-warning text-dark' : 'bg-info text-dark');
                        @endphp
                        <span class="badge {{ $badgeClass }}">{{ $score }}%</span>
                      </td>
                      <td>
                        <small class="text-muted text-break">{{ \Illuminate\Support\Str::limit($dup['entity_a_iri'] ?? '', 50) }}</small>
                      </td>
                      <td>
                        <small class="text-muted text-break">{{ \Illuminate\Support\Str::limit($dup['entity_b_iri'] ?? '', 50) }}</small>
                      </td>
                      <td><span class="badge bg-light text-dark">{{ $dup['entity_type'] ?? 'Record' }}</span></td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <a href="{{ route('dedupe.compare', $dup['id']) }}" class="btn atom-btn-white" title="Compare">
                            <i class="fas fa-columns"></i>
                          </a>
                          <button type="button" class="btn atom-btn-white btn-dismiss" data-id="{{ $dup['id'] }}" title="Dismiss">
                            <i class="fas fa-times"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>

    {{-- Sidebar --}}
    <div class="col-lg-4">
      {{-- Detection Methods --}}
      <div class="card mb-4">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Detection Methods</strong></div>
        <ul class="list-group list-group-flush">
          @forelse($stats['methodCounts'] ?? [] as $mc)
            <li class="list-group-item d-flex justify-content-between align-items-center">
              {{ $mc['detection_method'] ?? 'Unknown' }}
              <span class="badge bg-secondary rounded-pill">{{ number_format($mc['total'] ?? 0) }}</span>
            </li>
          @empty
            <li class="list-group-item text-muted">No detection data.</li>
          @endforelse
        </ul>
      </div>

      {{-- Recent Scans --}}
      <div class="card mb-4">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Recent Scans</strong></div>
        <ul class="list-group list-group-flush">
          @forelse($stats['recentScans'] ?? [] as $scan)
            <li class="list-group-item">
              <div class="d-flex justify-content-between">
                <span>
                  @if(($scan['status'] ?? '') === 'completed')
                    <span class="badge bg-success">Completed</span>
                  @elseif(($scan['status'] ?? '') === 'running')
                    <span class="badge bg-primary">Running</span>
                  @elseif(($scan['status'] ?? '') === 'failed')
                    <span class="badge bg-danger">Failed</span>
                  @else
                    <span class="badge bg-secondary">{{ ucfirst($scan['status'] ?? 'pending') }}</span>
                  @endif
                </span>
                <small class="text-muted">{{ $scan['created_at'] ?? '' }}</small>
              </div>
              <small>
                {{ number_format($scan['processed_records'] ?? 0) }}/{{ number_format($scan['total_records'] ?? 0) }} records
                &middot;
                {{ number_format($scan['duplicates_found'] ?? 0) }} duplicates found
              </small>
            </li>
          @empty
            <li class="list-group-item text-muted">No scans yet.</li>
          @endforelse
        </ul>
      </div>

      {{-- Quick Links --}}
      <div class="card mb-4">
        <div class="card-header" style="background:var(--bs-primary, #0d6efd);color:#fff"><strong>Quick Links</strong></div>
        <div class="list-group list-group-flush">
          <a href="{{ route('dedupe.records') }}" class="list-group-item list-group-item-action">
            <i class="fas fa-list me-2"></i> Browse All Duplicates
          </a>
          <a href="{{ route('dedupe.records', ['status' => 'pending']) }}" class="list-group-item list-group-item-action">
            <i class="fas fa-clock me-2"></i> Pending Review
          </a>
          <a href="{{ route('dedupe.agents') }}" class="list-group-item list-group-item-action">
            <i class="fas fa-users me-2"></i> Agent Duplicates
          </a>
          <a href="{{ route('dedupe.rules') }}" class="list-group-item list-group-item-action">
            <i class="fas fa-cog me-2"></i> Detection Rules
          </a>
          <a href="{{ route('dedupe.report') }}" class="list-group-item list-group-item-action">
            <i class="fas fa-chart-bar me-2"></i> Report
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-dismiss').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            if (!confirm('Dismiss this duplicate pair?')) return;
            fetch('{{ url("/dedupe/resolve") }}/' + id, {
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
                    var row = document.getElementById('dup-row-' + id);
                    if (row) row.remove();
                }
            });
        });
    });
});
</script>
@endpush
