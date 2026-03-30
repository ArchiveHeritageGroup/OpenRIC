@extends('theme::layouts.1col')

@section('title', 'Embargo Management')
@section('body-class', 'embargo index')

@section('content')
  <h1 class="mb-3">Embargo Management</h1>

  @if(isset($expiringEmbargoes) && count($expiringEmbargoes) > 0)
    <div class="alert alert-warning">
      <h5>Embargoes Expiring Within 30 Days</h5>
      <ul class="mb-0">
        @foreach(array_slice($expiringEmbargoes, 0, 5) as $embargo)
          <li>
            <a href="{{ route('rights.embargo.view', $embargo->id) }}">{{ $embargo->entity_iri }}</a>
            - Expires: {{ $embargo->embargo_end }}
          </li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card">
    <div class="card-header bg-primary text-white"><h4 class="mb-0">Active Embargoes</h4></div>
    <div class="card-body p-0">
      @if(isset($activeEmbargoes) && count($activeEmbargoes) > 0)
        <table class="table table-striped table-hover mb-0">
          <thead><tr><th>Entity IRI</th><th>Start Date</th><th>End Date</th><th>Reason</th><th>Actions</th></tr></thead>
          <tbody>
            @foreach($activeEmbargoes as $embargo)
              <tr>
                <td><code class="small">{{ Str::limit($embargo->entity_iri, 60) }}</code></td>
                <td>{{ $embargo->embargo_start ?? '-' }}</td>
                <td>{{ $embargo->embargo_end ?? 'Perpetual' }}</td>
                <td>{{ $embargo->reason ?? '-' }}</td>
                <td><a href="{{ route('rights.embargo.view', $embargo->id) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      @else
        <p class="text-muted text-center py-4">No active embargoes.</p>
      @endif
    </div>
  </div>

  @auth
    <div class="mt-3">
      <a href="{{ route('rights.embargo.add') }}" class="btn btn-outline-primary">Add new embargo</a>
    </div>
  @endauth
@endsection
