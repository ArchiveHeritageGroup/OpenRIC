@extends('theme::layouts.2col')

@section('title', 'Standards Mapping')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <h1 class="h3 mb-4">Standards Mapping Tables</h1>
    <p class="text-muted">These mappings define how traditional archival standards are rendered as lenses on the RiC-O graph.</p>

    {{-- ISAD(G) → RiC-O --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="card-title mb-0">ISAD(G) → RiC-O Mapping</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:8%;">Code</th>
                            <th style="width:30%;">ISAD(G) Element</th>
                            <th style="width:30%;">RiC-O Property</th>
                            <th style="width:20%;">RiC-O Class</th>
                            <th style="width:12%;">Required</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $currentArea = ''; @endphp
                        @foreach($isadg as $code => $field)
                            @php
                                $area = substr($code, 0, 3);
                                $areas = [
                                    '3.1' => 'Identity Statement Area',
                                    '3.2' => 'Context Area',
                                    '3.3' => 'Content and Structure Area',
                                    '3.4' => 'Conditions of Access and Use Area',
                                    '3.5' => 'Allied Materials Area',
                                    '3.6' => 'Notes Area',
                                    '3.7' => 'Description Control Area',
                                ];
                            @endphp
                            @if($area !== $currentArea)
                                @php $currentArea = $area; @endphp
                                <tr class="table-secondary">
                                    <td colspan="5" class="fw-bold">{{ $areas[$area] ?? $area }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td><code>{{ $code }}</code></td>
                                <td>{{ $field['label'] }}</td>
                                <td><code class="text-primary">{{ $field['rico_property'] }}</code></td>
                                <td>
                                    @if($field['rico_class'])
                                        <code class="text-success">{{ $field['rico_class'] }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>
                                    @if($field['required'] ?? false)
                                        <span class="badge bg-danger">Required</span>
                                    @else
                                        <span class="text-muted">Optional</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ISAAR-CPF → RiC-O --}}
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="card-title mb-0">ISAAR-CPF → RiC-O Mapping</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:8%;">Code</th>
                            <th style="width:35%;">ISAAR-CPF Element</th>
                            <th style="width:35%;">RiC-O Property</th>
                            <th style="width:22%;">RiC-O Class</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $currentSection = ''; @endphp
                        @foreach($isaarCpf as $code => $field)
                            @php
                                $section = substr($code, 0, 3);
                                $sections = [
                                    '5.1' => 'Identity Area',
                                    '5.2' => 'Description Area',
                                    '5.3' => 'Relationships Area',
                                ];
                            @endphp
                            @if($section !== $currentSection)
                                @php $currentSection = $section; @endphp
                                <tr class="table-secondary">
                                    <td colspan="4" class="fw-bold">{{ $sections[$section] ?? $section }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td><code>{{ $code }}</code></td>
                                <td>{{ $field['label'] }}</td>
                                <td><code class="text-primary">{{ $field['rico_property'] }}</code></td>
                                <td>
                                    @if($field['rico_class'] ?? null)
                                        <code class="text-success">{{ $field['rico_class'] }}</code>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="alert alert-info">
        <strong>Note:</strong> These mappings are defined in <code>config/openric.php</code> and are community-maintained.
        They follow the ICA EGAD guidance in RiC-CM 1.0 and the RiC Application Guidelines.
    </div>
@endsection
