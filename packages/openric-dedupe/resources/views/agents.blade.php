@extends('theme::layouts.1col')

@section('title', 'Agent Duplicates')
@section('body-class', 'browse dedupe agents')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-users me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">
        @if($total > 0)
          Showing {{ number_format($total) }} agent duplicate pairs
        @else
          No agent duplicates found
        @endif
      </h1>
      <span class="small text-muted">Duplicate Detection &mdash; Agents</span>
    </div>
    <div class="ms-auto d-flex gap-2">
      <a href="{{ route('dedupe.records') }}" class="btn atom-btn-white"><i class="fas fa-file me-1"></i> Record Duplicates</a>
      <a href="{{ route('dedupe.dashboard') }}" class="btn atom-btn-white"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
    </div>
  </div>

  <form method="GET" action="{{ route('dedupe.agents') }}" class="row g-2 mb-4 align-items-end">
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
      <button type="submit" class="btn atom-btn-outline-light btn-sm"><i class="fas fa-filter me-1"></i> Filter</button>
      <a href="{{ route('dedupe.agents') }}" class="btn btn-sm atom-btn-white">Reset</a>
    </div>
  </form>

  @if($total > 0)
    <div class="card mb-3">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered table-striped mb-0">
            <thead>
              <tr>
                <th style="width: 70px;">Score</th>
                <th>Agent A</th>
                <th>Agent B</th>
                <th>Status</th>
                <th>Detected</th>
                <th style="width: 140px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($hits as $dup)
                <tr>
                  <td class="text-center">
                    @php $score = round(($dup['similarity_score'] ?? 0) * 100); @endphp
                    <span class="badge {{ $score >= 90 ? 'bg-danger' : ($score >= 75 ? 'bg-warning text-dark' : 'bg-info text-dark') }}">{{ $score }}%</span>
                  </td>
                  <td><small class="text-break">{{ \Illuminate\Support\Str::limit($dup['entity_a_iri'] ?? '', 50) }}</small></td>
                  <td><small class="text-break">{{ \Illuminate\Support\Str::limit($dup['entity_b_iri'] ?? '', 50) }}</small></td>
                  <td><span class="badge {{ ($dup['status'] ?? '') === 'pending' ? 'bg-warning text-dark' : 'bg-secondary' }}">{{ ucfirst(str_replace('_', ' ', $dup['status'] ?? '')) }}</span></td>
                  <td><small class="text-muted">{{ isset($dup['created_at']) ? \Carbon\Carbon::parse($dup['created_at'])->format('M j, Y') : '-' }}</small></td>
                  <td>
                    <div class="btn-group btn-group-sm">
                      <a href="{{ route('dedupe.compare', $dup['id']) }}" class="btn atom-btn-white" title="Compare"><i class="fas fa-columns"></i></a>
                      @if(($dup['status'] ?? '') !== 'merged')
                        <a href="{{ route('dedupe.merge', $dup['id']) }}" class="btn atom-btn-white" title="Merge"><i class="fas fa-compress-arrows-alt"></i></a>
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

    @if($total > $limit)
      <nav class="mt-3"><ul class="pagination">
        @if($page > 1)<li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page - 1]) }}">Previous</a></li>@endif
        <li class="page-item disabled"><span class="page-link">Page {{ $page }} of {{ ceil($total / $limit) }}</span></li>
        @if($page * $limit < $total)<li class="page-item"><a class="page-link" href="{{ request()->fullUrlWithQuery(['page' => $page + 1]) }}">Next</a></li>@endif
      </ul></nav>
    @endif
  @endif
@endsection
