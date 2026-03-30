@extends('theme::layouts.1col')

@section('title', 'Expiring Embargoes')

@section('content')
  <h1 class="mb-3">Embargoes Expiring Within {{ $days }} Days</h1>

  @if(count($embargoes) > 0)
    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead><tr><th>Entity IRI</th><th>End Date</th><th>Reason</th></tr></thead>
        <tbody>
          @foreach($embargoes as $embargo)
            <tr>
              <td><code class="small">{{ $embargo->entity_iri }}</code></td>
              <td>{{ $embargo->embargo_end }}</td>
              <td>{{ $embargo->reason ?? '-' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @else
    <div class="alert alert-info">No embargoes expiring within {{ $days }} days.</div>
  @endif
@endsection
