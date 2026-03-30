@extends('theme::layouts.1col')

@section('title', 'Condition Report #' . $report->id)

@section('content')
    <h1 class="h3">Condition Report</h1>
    <p class="text-muted">Record: <a href="{{ route('records.show', ['iri' => urlencode($record->iri ?? '')]) }}">{{ $record->title ?? '[Untitled]' }}</a></p>

    <div class="card mb-3">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-3">Assessment Date</dt><dd class="col-sm-9">{{ $report->assessment_date }}</dd>
                <dt class="col-sm-3">Overall Rating</dt><dd class="col-sm-9"><span class="badge bg-secondary">{{ ucfirst($report->overall_rating) }}</span></dd>
                <dt class="col-sm-3">Context</dt><dd class="col-sm-9">{{ ucfirst($report->context ?? '') }}</dd>
                <dt class="col-sm-3">Priority</dt><dd class="col-sm-9">{{ ucfirst($report->priority ?? 'normal') }}</dd>
                @if($report->summary)<dt class="col-sm-3">Summary</dt><dd class="col-sm-9">{!! nl2br(e($report->summary)) !!}</dd>@endif
                @if($report->recommendations)<dt class="col-sm-3">Recommendations</dt><dd class="col-sm-9">{!! nl2br(e($report->recommendations)) !!}</dd>@endif
                @if($report->next_check_date)<dt class="col-sm-3">Next Check</dt><dd class="col-sm-9">{{ $report->next_check_date }}</dd>@endif
            </dl>
        </div>
    </div>

    @if(isset($report->damages) && $report->damages->isNotEmpty())
        <h5>Damages</h5>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead><tr><th>Type</th><th>Location</th><th>Severity</th><th>Description</th></tr></thead>
                <tbody>
                    @foreach($report->damages as $damage)
                        <tr>
                            <td>{{ ucfirst(str_replace('_', ' ', $damage->damage_type)) }}</td>
                            <td>{{ ucfirst($damage->location ?? '') }}</td>
                            <td><span class="badge bg-{{ $damage->severity === 'critical' || $damage->severity === 'severe' ? 'danger' : ($damage->severity === 'moderate' ? 'warning' : 'secondary') }}">{{ ucfirst($damage->severity ?? '') }}</span></td>
                            <td>{{ $damage->description ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <a href="{{ route('record.condition.index', $record->id) }}" class="btn btn-outline-secondary">Back to reports</a>
@endsection
