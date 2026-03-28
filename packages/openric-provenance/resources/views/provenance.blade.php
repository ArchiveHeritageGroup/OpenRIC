@extends('theme::layouts.2col')

@section('title', 'Provenance')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <h1 class="h3 mb-3">Provenance</h1>
    <p class="text-muted mb-4">
        Activities and custody chain for <code class="small">{{ $iri }}</code>
    </p>

    @include('theme::partials.alerts')

    {{-- Custody Chain --}}
    @if(count($custodyChain) > 0)
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Custody Chain</h5>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    @foreach($custodyChain as $i => $link)
                        @if($i > 0)
                            <span class="text-muted">&rarr;</span>
                        @endif
                        <div class="border rounded p-2 bg-light">
                            <strong>{{ $link['holderName']['value'] ?? 'Unknown' }}</strong>
                            @if(!empty($link['date']['value']))
                                <br><small class="text-muted">{{ $link['date']['value'] }}</small>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    {{-- Activity Timeline --}}
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Activity Timeline</h5>
        </div>
        <div class="card-body">
            @if(count($timeline) > 0)
                <div class="position-relative ps-4" style="border-left: 3px solid #ffc107;">
                    @foreach($timeline as $activity)
                        <div class="card mb-2 ms-2">
                            <div class="card-body py-2 px-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <span class="badge bg-warning text-dark">{{ $activity['activityType']['value'] ?? 'Activity' }}</span>
                                        {{ $activity['description']['value'] ?? '' }}
                                    </div>
                                    <span class="text-muted small">{{ $activity['date']['value'] ?? '' }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted">No activities recorded for this entity.</p>
            @endif
        </div>
    </div>
@endsection
