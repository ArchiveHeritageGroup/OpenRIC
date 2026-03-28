@extends('theme::layouts.2col')

@section('title', $isadg['3.1.2']['value'] ?? 'Record Set')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">{{ $isadg['3.1.2']['value'] ?? 'Record Set' }}</h1>
        <div class="d-flex gap-2">
            @include('theme::partials.view-switch')
            <a href="{{ route('record-sets.edit', ['iri' => urlencode($entity['iri'])]) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        </div>
    </div>

    @include('theme::partials.alerts')

    @php
        $areas = [];
        foreach ($isadg as $code => $field) {
            $area = $field['area'] ?? 'Other';
            $areas[$area][$code] = $field;
        }
    @endphp

    @foreach($areas as $areaName => $fields)
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">{{ $areaName }}</h5>
            </div>
            <div class="card-body">
                <dl class="row mb-0">
                    @foreach($fields as $code => $field)
                        <dt class="col-sm-4">
                            <span class="text-muted small">{{ $code }}</span>
                            {{ $field['label'] }}
                            @if($field['required'] ?? false)
                                <span class="text-danger">*</span>
                            @endif
                        </dt>
                        <dd class="col-sm-8">
                            {{ $field['value'] ?? '-' }}
                        </dd>
                    @endforeach
                </dl>
            </div>
        </div>
    @endforeach

    @if(count($children ?? []) > 0)
        <div class="card mb-3">
            <div class="card-header bg-light">
                <h5 class="card-title mb-0">Sub-levels</h5>
            </div>
            <ul class="list-group list-group-flush">
                @foreach($children as $child)
                    <li class="list-group-item">
                        <a href="{{ route('record-sets.show', ['iri' => urlencode($child['child']['value'] ?? '')]) }}">
                            {{ $child['title']['value'] ?? 'Untitled' }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection
