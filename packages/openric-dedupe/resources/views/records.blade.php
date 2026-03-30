@extends('theme::layouts.1col')

@section('title', 'Browse Duplicates')
@section('body-class', 'browse dedupe')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-clone me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">
        @if($total > 0)
          Showing {{ number_format($total) }} results
        @else
          No results found
        @endif
      </h1>
      <span class="small text-muted">Duplicate Detection &mdash; Records</span>
    </div>
    <div class="ms-auto">
      <a href="{{ route('dedupe.dashboard') }}" class="btn atom-btn-white">
        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
      </a>
    </div>
  </div>

  {{-- Filters --}}
  <form method="GET" action="{{ route('dedupe.records') }}" class="row g-2 mb-4 align-items-end">
    <div class="col-auto">
      <label class="form-label small mb-1">Status</label>
      <select name="status" class="form-select form-select-sm">
        @foreach($statusOptions as $val => $label)
          <option value="{{ $val }}" {{ ($filters['status'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-auto">
      <label class="form-label small mb-1">Min Score</label>
      <select name="minScore" class="form-select form-select-sm">
        <option value="">Any</option>
        @foreach([0.5, 0.6, 0.7, 0.75, 0.8, 0.85, 0.9, 0.95] as $s)
          <option value="{{ $s }}" {{ ($filters['minScore'] ?? '') == $s ? 'selected' : '' }}>{{ $s * 100 }}%</option>
        @endforeach
      </select>
    </div>
    <div class="col-auto">
      <button type="submit" class="btn atom-btn-outline-light btn-sm">
        <i class="fas fa-filter me-1"></i> Filter
      </button>
      <a href="{{ route('dedupe.records') }}" class="btn btn-sm atom-btn-white">Reset</a>
    </div>
  </form>

  @if($total > 0)
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center" style="background:var(--bs-primary, #0d6efd);color:#fff">
        <span><strong>{{ number_format($total) }}</strong> duplicate pairs found</span>
        <div class="btn-group btn-group-sm">
          <button type="button" class="btn btn-outline-light" id="selectAll">
            <i class="fas fa-check-square me-1"></i> Select All
          </button>
          <button type="button" class="btn btn-outline-light" id="dismissSelected" disabled>
            <i class="fas fa-times me-1"></i> Dismiss Selected
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-striped mb-0">
            <thead>
              <tr>
                <th style="width: 40px;"><input type="checkbox" class="form-check-input" id="checkAll"></th>
                <th style="width: 70px;">Score</th>
                <th>Entity A</th>
                <th>Entity B</th>
                <th>Type</th>
                <th>Status</th>
                <th>Detected</th>
                <th style="width: 140px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($hits as $dup)
                <tr id="dup-row-{{ $dup['id'] }}">
                  <td><input type="checkbox" class="form-check-input row-check" data-id="{{ $dup['id'] }}"></td>
                  <td class="text-center">
                    @php
                      $score = round(($dup['similarity_score'] ?? 0) * 100);
                      $badgeClass = $score >= 90 ? 'bg-danger' : ($score >= 75 ? 'bg-warning text-dark' : 'bg-info text-dark');
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $score }}%</span>
                  </td>
                  <td><small class="text-break">{{ \Illuminate\Support\Str::limit($dup['entity_a_iri'] ?? '', 45) }}</small></td>
                  <td><small class="text-break">{{ \Illuminate\Support\Str::limit($dup['entity_b_iri'] ?? '', 45) }}</small></td>
                  <td><span class="badge bg-light text-dark">{{ $dup['entity_type'] ?? 'Record' }}</span></td>
                  <td>
                    @php
                      $statusColors = ['pending' => 'bg-warning text-dark', 'merged' => 'bg-success', 'not_duplicate' => 'bg-secondary'];
                    @endphp
                    <span class="badge {{ $statusColors[$dup['status']] ?? 'bg-secondary' }}">{{ ucfirst(str_replace('_', ' ', $dup['status'] ?? '')) }}</span>
                  </td>
                  <td><small class="text-muted">{{ $dup['created_at'] ? \Carbon\Carbon::parse($dup['created_at'])->format('M j, Y') : '-' }}</small></td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <a href="{{ route('dedupe.compare', $dup['id']) }}" class="btn atom-btn-white" title="Compare Side-by-Side">
                        <i class="fas fa-columns"></i>
                      </a>
                      @if(($dup['status'] ?? '') !== 'merged')
                        <a href="{{ route('dedupe.merge', $dup['id']) }}" class="btn atom-btn-white" title="Merge Records">
                          <i class="fas fa-compress-arrows-alt"></i>
                        </a>
                        <button type="button" class="btn atom-btn-white btn-dismiss" data-id="{{ $dup['id'] }}" title="Dismiss">
                          <i class="fas fa-times"></i>
                        </button>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- Pagination --}}
    @if($total > $limit)
      <nav aria-label="Pagination" class="mt-3">
        <ul class="pagination">
          @if($page > 1)
            <li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}">Previous</a></li>
          @endif
          <li class="page-item disabled"><span class="page-link">Page {{ $page }} of {{ ceil($total / $limit) }}</span></li>
          @if($page * $limit < $total)
            <li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}">Next</a></li>
          @endif
        </ul>
      </nav>
    @endif
  @endif
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    var checkAll = document.getElementById('checkAll');
    var rowChecks = document.querySelectorAll('.row-check');
    var dismissSelected = document.getElementById('dismissSelected');
    var selectAllBtn = document.getElementById('selectAll');

    function updateDismissButton() {
        var checked = document.querySelectorAll('.row-check:checked');
        if (dismissSelected) dismissSelected.disabled = checked.length === 0;
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            rowChecks.forEach(function(cb) { cb.checked = checkAll.checked; });
            updateDismissButton();
        });
    }

    rowChecks.forEach(function(cb) {
        cb.addEventListener('change', updateDismissButton);
    });

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function() {
            rowChecks.forEach(function(cb) { cb.checked = true; });
            if (checkAll) checkAll.checked = true;
            updateDismissButton();
        });
    }

    if (dismissSelected) {
        dismissSelected.addEventListener('click', function() {
            var checked = document.querySelectorAll('.row-check:checked');
            if (checked.length === 0) return;
            if (!confirm('Dismiss ' + checked.length + ' duplicate pair(s)?')) return;
            var ids = Array.from(checked).map(function(cb) { return cb.dataset.id; });
            ids.forEach(function(id) {
                fetch('{{ url("/dedupe/resolve") }}/' + id, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({resolution: 'not_duplicate'}),
                });
            });
            setTimeout(function() { location.reload(); }, 500);
        });
    }

    document.querySelectorAll('.btn-dismiss').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = this.getAttribute('data-id');
            if (!confirm('Dismiss this duplicate pair?')) return;
            var row = this.closest('tr');
            fetch('{{ url("/dedupe/resolve") }}/' + id, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({resolution: 'not_duplicate'}),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && row) row.remove();
            });
        });
    });
});
</script>
@endpush
