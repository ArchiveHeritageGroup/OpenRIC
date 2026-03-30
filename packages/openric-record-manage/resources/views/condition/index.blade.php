@extends('theme::layouts.1col')

@section('title', 'Condition: ' . ($record->title ?? '[Untitled]'))

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Condition Assessment</h1>
        <a href="{{ route('record.condition.create', $record->id) }}" class="btn btn-primary btn-sm">New Report</a>
    </div>

    <p class="text-muted">Record: <a href="{{ route('records.show', ['iri' => urlencode($record->iri ?? '')]) }}">{{ $record->title ?? '[Untitled]' }}</a></p>

    @include('theme::partials.alerts')

    @if($latest)
        <div class="card mb-3">
            <div class="card-header fw-bold">Latest Assessment</div>
            <div class="card-body">
                <dl class="row mb-0">
                    <dt class="col-sm-3">Date</dt><dd class="col-sm-9">{{ $latest->assessment_date }}</dd>
                    <dt class="col-sm-3">Rating</dt><dd class="col-sm-9"><span class="badge bg-{{ $latest->overall_rating === 'good' || $latest->overall_rating === 'excellent' ? 'success' : ($latest->overall_rating === 'fair' ? 'warning' : 'danger') }}">{{ ucfirst($latest->overall_rating) }}</span></dd>
                    <dt class="col-sm-3">Context</dt><dd class="col-sm-9">{{ ucfirst($latest->context ?? 'routine') }}</dd>
                    @if($latest->summary)<dt class="col-sm-3">Summary</dt><dd class="col-sm-9">{{ $latest->summary }}</dd>@endif
                </dl>
            </div>
        </div>
    @endif

    @if($reports->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead><tr><th>Date</th><th>Rating</th><th>Context</th><th>Summary</th><th></th></tr></thead>
                <tbody>
                    @foreach($reports as $report)
                        <tr>
                            <td>{{ $report->assessment_date }}</td>
                            <td><span class="badge bg-secondary">{{ ucfirst($report->overall_rating) }}</span></td>
                            <td>{{ ucfirst($report->context ?? '') }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($report->summary ?? '', 80) }}</td>
                            <td><a href="{{ route('record.condition.show', $report->id) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p>No condition reports yet.</p>
    @endif
@endsection
