@extends('theme::layouts.1col')

@section('title', 'Preservation: ' . ($record->title ?? '[Untitled]'))

@section('content')
    <h1 class="h3">Preservation</h1>
    <p class="text-muted">Record: <a href="{{ route('records.show', ['iri' => urlencode($record->iri ?? '')]) }}">{{ $record->title ?? '[Untitled]' }}</a></p>

    @include('theme::partials.alerts')

    <h5>Archival Information Packages (AIPs)</h5>
    @if($aips->isNotEmpty())
        <div class="table-responsive mb-4">
            <table class="table table-striped table-sm">
                <thead><tr><th>UUID</th><th>Filename</th><th>Size</th><th>Files</th><th>Created</th></tr></thead>
                <tbody>
                    @foreach($aips as $aip)
                        <tr>
                            <td><code class="small">{{ $aip->uuid ?? '' }}</code></td>
                            <td>{{ $aip->filename ?? '' }}</td>
                            <td>{{ isset($aip->size_on_disk) ? number_format($aip->size_on_disk / 1048576, 2) . ' MB' : '' }}</td>
                            <td>{{ $aip->digital_object_count ?? 0 }}</td>
                            <td>{{ $aip->created_at ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p>No AIPs linked to this record.</p>
    @endif

    <h5>PREMIS Objects</h5>
    @if($premisObjects->isNotEmpty())
        <div class="table-responsive">
            <table class="table table-striped table-sm">
                <thead><tr><th>Filename</th><th>PUID</th><th>MIME Type</th><th>Size</th><th>Ingested</th></tr></thead>
                <tbody>
                    @foreach($premisObjects as $po)
                        <tr>
                            <td>{{ $po->filename ?? '' }}</td>
                            <td>{{ $po->puid ?? '' }}</td>
                            <td>{{ $po->mime_type ?? '' }}</td>
                            <td>{{ isset($po->size) ? number_format($po->size) . ' bytes' : '' }}</td>
                            <td>{{ $po->date_ingested ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p>No PREMIS objects linked to this record.</p>
    @endif
@endsection
