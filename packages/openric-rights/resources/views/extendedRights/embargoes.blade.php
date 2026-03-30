@extends('theme::layouts.1col')

@section('title', 'Active Embargoes')

@section('content')
  <h1 class="mb-3">Active Embargoes</h1>

  @if(count($embargoes) > 0)
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead><tr><th>Entity IRI</th><th>Start</th><th>End</th><th>Reason</th><th>Actions</th></tr></thead>
        <tbody>
          @foreach($embargoes as $embargo)
            <tr>
              <td><code class="small">{{ Str::limit($embargo->entity_iri, 60) }}</code></td>
              <td>{{ $embargo->embargo_start }}</td>
              <td>{{ $embargo->embargo_end ?? 'Perpetual' }}</td>
              <td>{{ $embargo->reason ?? '-' }}</td>
              <td>
                <form method="POST" action="{{ route('rights.extended.lift-embargo', $embargo->id) }}" class="d-inline">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Lift this embargo?')">Lift</button>
                </form>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="alert alert-info">No active embargoes.</div>
  @endif
@endsection
