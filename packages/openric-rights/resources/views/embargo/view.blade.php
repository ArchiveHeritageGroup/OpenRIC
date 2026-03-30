@extends('theme::layouts.1col')

@section('title', 'Embargo Details')

@section('content')
  <h1 class="mb-3">Embargo Details</h1>

  @php $status = $embargo['status'] ?? 'active'; @endphp

  <div class="card mb-4">
    <div class="card-header bg-primary text-white">
      <h4 class="mb-0">Embargo Information <span class="badge bg-{{ $status === 'active' ? 'danger' : ($status === 'lifted' ? 'success' : 'secondary') }} float-end">{{ ucfirst($status) }}</span></h4>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6">
          <dl>
            <dt>Entity IRI</dt><dd><code>{{ $embargo['entity_iri'] ?? '' }}</code></dd>
            <dt>Start Date</dt><dd>{{ $embargo['embargo_start'] ?? '-' }}</dd>
            <dt>End Date</dt><dd>{{ $embargo['embargo_end'] ?? 'Perpetual' }}</dd>
          </dl>
        </div>
        <div class="col-md-6">
          <dl>
            @if(!empty($embargo['reason']))<dt>Reason</dt><dd>{{ $embargo['reason'] }}</dd>@endif
          </dl>
        </div>
      </div>

      @if($status === 'lifted')
        <div class="alert alert-success">
          <strong>This embargo was lifted</strong>
          @if($embargo['lifted_at'] ?? null) on {{ $embargo['lifted_at'] }} @endif
        </div>
      @endif
    </div>
  </div>

  {{-- Audit Log --}}
  <div class="card">
    <div class="card-header bg-primary text-white"><h4 class="mb-0">Audit Log</h4></div>
    <div class="card-body">
      @if(!empty($auditLog))
        <table class="table table-sm">
          <thead><tr><th>Date</th><th>Action</th><th>User</th><th>IP</th></tr></thead>
          <tbody>
            @foreach($auditLog as $log)
              <tr>
                <td>{{ $log->created_at }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                <td>{{ $log->user_id ?: '-' }}</td>
                <td>{{ $log->ip_address ?? '-' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <p class="text-muted">No audit log entries.</p>
      @endif
    </div>
  </div>
@endsection

@section('after-content')
  @auth
    <ul class="actions mb-3 nav gap-2">
      @if($status === 'active')
        <li><a href="{{ route('rights.embargo.liftForm', $embargo['id']) }}" class="btn btn-outline-success">Lift Embargo</a></li>
      @endif
      <li><a href="{{ route('rights.embargo.index') }}" class="btn btn-outline-secondary">Back to Embargoes</a></li>
    </ul>
  @endauth
@endsection
