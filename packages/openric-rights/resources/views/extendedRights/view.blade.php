@extends('theme::layouts.1col')

@section('title', 'Extended Rights — View')

@section('content')
  <h1 class="mb-3">Extended Rights</h1>
  <p class="text-muted">Entity: <code>{{ $entityIri ?: 'none specified' }}</code></p>

  @if(!empty($statements))
    <div class="card mb-4">
      <div class="card-header bg-primary text-white"><h5 class="mb-0">Rights Statements</h5></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0"><thead><tr><th>Basis</th><th>Terms</th><th>Holder</th><th>Dates</th></tr></thead><tbody>
          @foreach($statements as $s)
            <tr><td>{{ ucfirst($s->rights_basis ?? '') }}</td><td>{{ $s->terms ?? '' }}</td><td>{{ $s->rights_holder_name ?? '' }}</td><td>{{ $s->start_date ?? '' }} - {{ $s->end_date ?? '' }}</td></tr>
          @endforeach
        </tbody></table>
      </div>
    </div>
  @endif

  @if(!empty($embargoes))
    <div class="card mb-4">
      <div class="card-header bg-warning text-dark"><h5 class="mb-0">Embargoes</h5></div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0"><thead><tr><th>Status</th><th>Start</th><th>End</th><th>Reason</th></tr></thead><tbody>
          @foreach($embargoes as $e)
            <tr><td><span class="badge bg-{{ $e->status === 'active' ? 'danger' : 'secondary' }}">{{ ucfirst($e->status) }}</span></td><td>{{ $e->embargo_start }}</td><td>{{ $e->embargo_end ?? 'Perpetual' }}</td><td>{{ $e->reason ?? '-' }}</td></tr>
          @endforeach
        </tbody></table>
      </div>
    </div>
  @endif

  @if(!empty($tkLabels))
    <div class="card mb-4">
      <div class="card-header bg-success text-white"><h5 class="mb-0">TK Labels</h5></div>
      <div class="card-body">
        @foreach($tkLabels as $tk)
          <span class="badge bg-secondary me-1 mb-1">{{ $tk->label_type }}</span>
        @endforeach
      </div>
    </div>
  @endif

  @if(empty($statements) && empty($embargoes) && empty($tkLabels))
    <div class="alert alert-info">No rights data found for this entity.</div>
  @endif
@endsection
