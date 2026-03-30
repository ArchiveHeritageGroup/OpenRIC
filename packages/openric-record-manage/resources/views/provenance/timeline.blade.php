@extends('theme::layouts.1col')

@section('title', 'Provenance Timeline: ' . ($record->title ?? '[Untitled]'))

@section('content')
    <h1 class="h3">Provenance Timeline</h1>
    <p class="text-muted">Record: <a href="{{ route('records.show', ['iri' => urlencode($record->iri ?? '')]) }}">{{ $record->title ?? '[Untitled]' }}</a></p>

    <a href="{{ route('record.provenance.index', $record->id) }}" class="btn btn-outline-secondary btn-sm mb-3">Back to chain view</a>

    <div id="provenance-timeline" style="width:100%;min-height:400px;border:1px solid #dee2e6;border-radius:.375rem;background:#fafafa;"></div>

    @push('scripts')
    <script>
        const timelineData = {!! $timelineData !!};
        const container = document.getElementById('provenance-timeline');
        if (timelineData.length === 0) {
            container.innerHTML = '<p class="p-4 text-muted">No provenance data to display.</p>';
        } else {
            let html = '<div class="p-3">';
            timelineData.forEach(function(item, i) {
                const color = {'creation':'#28a745','sale':'#dc3545','gift':'#17a2b8','inheritance':'#6f42c1','auction':'#fd7e14','transfer':'#6c757d','loan':'#20c997','theft':'#e83e8c','recovery':'#ffc107'}[item.category] || '#6c757d';
                html += '<div class="d-flex align-items-start mb-3">';
                html += '<div style="width:12px;height:12px;border-radius:50%;background:'+color+';margin-top:6px;flex-shrink:0"></div>';
                html += '<div class="ms-3">';
                html += '<strong>'+item.label+'</strong> <span class="badge" style="background:'+color+'">'+item.type+'</span>';
                if (item.startDate) html += '<br><small class="text-muted">'+item.startDate+(item.endDate ? ' &mdash; '+item.endDate : '')+'</small>';
                if (item.location) html += '<br><small class="text-muted">Location: '+item.location+'</small>';
                if (item.description) html += '<br><small>'+item.description+'</small>';
                html += '</div></div>';
            });
            html += '</div>';
            container.innerHTML = html;
        }
    </script>
    @endpush
@endsection
