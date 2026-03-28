@extends('theme::layouts.2col')

@section('title', 'Relationships')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <h1 class="h3 mb-4">Relationships</h1>

    @include('theme::partials.alerts')

    @if($iri)
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Entity: <code class="small">{{ $iri }}</code></h5>
            </div>
        </div>

        {{-- Existing relationships --}}
        @if(count($relationships) > 0)
            <div class="table-responsive mb-4">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Direction</th>
                            <th>Predicate</th>
                            <th>Related Entity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($relationships as $rel)
                            @php
                                $isOutbound = ($rel['subject']['value'] ?? '') === $iri;
                                $relatedIri = $isOutbound ? ($rel['object']['value'] ?? '') : ($rel['subject']['value'] ?? '');
                                $predicate = $rel['predicate']['value'] ?? '';
                                $shortPred = str_replace('https://www.ica.org/standards/RiC/ontology#', 'rico:', $predicate);
                            @endphp
                            <tr>
                                <td>
                                    <span class="badge {{ $isOutbound ? 'bg-primary' : 'bg-success' }}">
                                        {{ $isOutbound ? 'Outbound' : 'Inbound' }}
                                    </span>
                                </td>
                                <td><code>{{ $shortPred }}</code></td>
                                <td>
                                    <a href="{{ route('relationships.index', ['iri' => $relatedIri]) }}">
                                        <code class="small">{{ \Illuminate\Support\Str::limit($relatedIri, 60) }}</code>
                                    </a>
                                </td>
                                <td>
                                    @if($isOutbound)
                                        <form method="POST" action="{{ route('relationships.destroy') }}" class="d-inline" onsubmit="return confirm('Remove this relationship?')">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="subject_iri" value="{{ $iri }}">
                                            <input type="hidden" name="predicate" value="{{ $predicate }}">
                                            <input type="hidden" name="object_iri" value="{{ $relatedIri }}">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-muted mb-4">No relationships found for this entity.</p>
        @endif

        {{-- Add new relationship --}}
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Add Relationship</h5></div>
            <div class="card-body">
                <form method="POST" action="{{ route('relationships.store') }}">
                    @csrf
                    <input type="hidden" name="subject_iri" value="{{ $iri }}">

                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="predicate" class="form-label">Predicate</label>
                            <select class="form-select" id="predicate" name="predicate" required>
                                <option value="">Select relationship type...</option>
                                @foreach($predicates as $pred => $label)
                                    <option value="{{ $pred }}">{{ $label }} ({{ $pred }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label for="object_iri" class="form-label">Target Entity IRI</label>
                            <input type="text" class="form-control" id="object_iri" name="object_iri" required placeholder="https://ric.theahg.co.za/entity/...">
                        </div>
                        <div class="col-md-2 mb-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Add</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body">
                <form method="GET" action="{{ route('relationships.index') }}">
                    <div class="input-group">
                        <input type="text" class="form-control" name="iri" placeholder="Enter entity IRI to manage relationships..." required>
                        <button class="btn btn-primary" type="submit">View Relationships</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection
