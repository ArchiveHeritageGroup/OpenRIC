@extends('theme::layouts.2col')

@section('title', 'Description History')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <h1 class="h3 mb-3">Description History</h1>
    <p class="text-muted mb-4">
        <code class="small">{{ $iri }}</code>
    </p>

    @include('theme::partials.alerts')

    {{-- Description Records (per RiC-CM Section 6) --}}
    @if(count($descriptionRecords) > 0)
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Description Records (RiC-CM Section 6)</h5>
            </div>
            <div class="card-body">
                <p class="text-muted small">Per RiC-CM, descriptions are Records that describe other Records via <code>rico:describesOrDescribed</code>.</p>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>Title</th><th>Form Type</th><th>Created</th><th>Creator</th></tr></thead>
                        <tbody>
                            @foreach($descriptionRecords as $desc)
                                <tr>
                                    <td>{{ $desc['title']['value'] ?? '-' }}</td>
                                    <td><code class="small">{{ str_replace('https://www.ica.org/standards/RiC/vocabularies/documentaryFormTypes#', '', $desc['formType']['value'] ?? '-') }}</code></td>
                                    <td>{{ $desc['createdDate']['value'] ?? '-' }}</td>
                                    <td><code class="small">{{ $desc['creator']['value'] ?? '-' }}</code></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Combined history --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Change History</h5>
        </div>
        <div class="card-body p-0">
            @if(count($history) > 0)
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Source</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $entry)
                                <tr>
                                    <td class="small">{{ $entry['timestamp'] }}</td>
                                    <td>
                                        @if($entry['source'] === 'rdf-star')
                                            <span class="badge bg-primary">RDF-Star</span>
                                        @else
                                            <span class="badge bg-secondary">Audit Log</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @php
                                            $userDisplay = $entry['user'];
                                            if (str_contains($userDisplay, '/user/')) {
                                                $userDisplay = substr($userDisplay, strrpos($userDisplay, '/') + 1);
                                            }
                                        @endphp
                                        {{ $userDisplay }}
                                    </td>
                                    <td><span class="badge bg-outline-dark border">{{ $entry['action'] }}</span></td>
                                    <td class="small">
                                        @if($entry['source'] === 'rdf-star')
                                            <code>{{ str_replace('https://www.ica.org/standards/RiC/ontology#', 'rico:', $entry['details']['predicate'] ?? '') }}</code>
                                            = {{ \Illuminate\Support\Str::limit($entry['details']['object'] ?? '', 60) }}
                                            @if(!empty($entry['details']['reason']))
                                                <br><em class="text-muted">{{ $entry['details']['reason'] }}</em>
                                            @endif
                                        @else
                                            {{ $entry['details']['description'] ?? $entry['action'] }}
                                            @if(!empty($entry['details']['changed_fields']))
                                                <br>Changed: {{ implode(', ', $entry['details']['changed_fields']) }}
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-3 text-muted text-center">No history entries found.</div>
            @endif
        </div>
    </div>
@endsection
