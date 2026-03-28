@extends('theme::layouts.2col')

@section('title', 'Timeline')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Timeline</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('graph.overview') }}" class="btn btn-outline-secondary btn-sm">Graph Overview</a>
            <a href="{{ route('graph.agent-network') }}" class="btn btn-outline-secondary btn-sm">Agent Network</a>
        </div>
    </div>
    <p class="text-muted">Entities ordered by date — traversal through time via <code>rico:isAssociatedWithDate</code>.</p>

    @if(count($timeline) > 0)
        <div class="timeline position-relative ps-4" style="border-left: 3px solid #0d6efd;">
            @php $currentYear = ''; @endphp
            @foreach($timeline as $entry)
                @php
                    $year = substr($entry['date'], 0, 4);
                    $showYear = $year !== $currentYear;
                    $currentYear = $year;

                    $typeColors = [
                        'RecordSet' => 'primary', 'Record' => 'info', 'RecordPart' => 'secondary',
                        'Person' => 'success', 'CorporateBody' => 'success', 'Family' => 'success',
                        'Activity' => 'warning', 'Place' => 'warning', 'Mandate' => 'danger',
                    ];
                    $badge = $typeColors[$entry['type']] ?? 'secondary';
                @endphp

                @if($showYear)
                    <div class="position-relative mb-2 mt-4">
                        <span class="position-absolute bg-primary text-white rounded-pill px-2 py-1 small" style="left:-2.1rem; transform:translateX(-50%);">{{ $year }}</span>
                    </div>
                @endif

                <div class="card mb-2 ms-2">
                    <div class="card-body py-2 px-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-{{ $badge }} me-1">{{ $entry['type'] }}</span>
                                <a href="{{ route('graph.entity', ['iri' => urlencode($entry['iri'])]) }}">{{ $entry['title'] }}</a>
                            </div>
                            <span class="text-muted small">{{ $entry['date'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="alert alert-info">No entities with dates found in the triplestore.</div>
    @endif
@endsection
