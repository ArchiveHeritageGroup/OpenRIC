@extends('theme::layouts.2col')

@section('title', 'Publish Readiness Check')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">
            <i class="fas fa-clipboard-check me-2" aria-hidden="true"></i>Publish Readiness
        </h1>
        <a href="{{ route('workflow.dashboard') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1" aria-hidden="true"></i>Dashboard
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- IRI Lookup Form --}}
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Check Entity</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('workflow.publish-readiness') }}" method="GET">
                <div class="row align-items-end">
                    <div class="col-md-9 mb-2">
                        <label for="iri" class="form-label">
                            Entity IRI <span class="text-danger" aria-hidden="true">*</span>
                            <span class="visually-hidden">(required)</span>
                        </label>
                        <input type="text" class="form-control" id="iri" name="iri" value="{{ $objectIri }}" required placeholder="Enter the entity IRI to evaluate" aria-describedby="iriHelp">
                        <div id="iriHelp" class="form-text">The full IRI of the RiC-O entity to check for publication readiness.</div>
                    </div>
                    <div class="col-md-3 mb-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1" aria-hidden="true"></i>Evaluate
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if($evaluation !== null)
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Results for: <code>{{ $objectIri }}</code></h5>
            </div>
            <div class="card-body">
                {{-- Summary --}}
                <div class="row text-center mb-4">
                    <div class="col-md-4 mb-2">
                        <div class="border rounded p-3">
                            <h3 class="text-success mb-0">{{ $evaluation['summary']['pass'] }}</h3>
                            <small class="text-muted">Passed</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="border rounded p-3">
                            <h3 class="text-danger mb-0">{{ $evaluation['summary']['fail'] }}</h3>
                            <small class="text-muted">Failed</small>
                        </div>
                    </div>
                    <div class="col-md-4 mb-2">
                        <div class="border rounded p-3">
                            <h3 class="text-warning mb-0">{{ $evaluation['summary']['warning'] }}</h3>
                            <small class="text-muted">Warnings</small>
                        </div>
                    </div>
                </div>

                {{-- Overall Verdict --}}
                @if($evaluation['summary']['fail'] === 0)
                    <div class="alert alert-success" role="alert">
                        <i class="fas fa-check-circle me-1" aria-hidden="true"></i>
                        <strong>Ready to publish.</strong> All gate rules passed.
                        @if($evaluation['summary']['warning'] > 0)
                            There {{ $evaluation['summary']['warning'] === 1 ? 'is' : 'are' }} {{ $evaluation['summary']['warning'] }} warning(s) to review.
                        @endif
                    </div>
                @else
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-times-circle me-1" aria-hidden="true"></i>
                        <strong>Not ready to publish.</strong>
                        {{ $evaluation['summary']['fail'] }} blocker(s) must be resolved before publishing.
                    </div>
                @endif

                {{-- Results Table --}}
                @if(count($evaluation['results']) === 0)
                    <p class="text-muted">No gate rules are configured.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Status</th>
                                    <th scope="col">Rule</th>
                                    <th scope="col">Type</th>
                                    <th scope="col">Severity</th>
                                    <th scope="col">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($evaluation['results'] as $result)
                                    <tr>
                                        <td>
                                            @if($result->status === 'pass')
                                                <span class="badge bg-success"><i class="fas fa-check" aria-hidden="true"></i> Pass</span>
                                            @elseif($result->status === 'fail')
                                                <span class="badge bg-danger"><i class="fas fa-times" aria-hidden="true"></i> Fail</span>
                                            @else
                                                <span class="badge bg-warning text-dark"><i class="fas fa-exclamation" aria-hidden="true"></i> Warning</span>
                                            @endif
                                        </td>
                                        <td>{{ $result->rule_name }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $result->rule_type)) }}</span></td>
                                        <td>
                                            @if($result->severity === 'blocker')
                                                <span class="badge bg-danger">Blocker</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Warning</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($result->details)
                                                <small class="text-muted">{{ $result->details }}</small>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
@endsection
