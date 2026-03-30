@extends('theme::layouts.print')

@section('title', $entity['properties']['rico:title'][0]['value'] ?? 'Record')

@section('content')
    @if(!empty($ancestors))
        <div style="font-size:10pt;color:#666;margin-bottom:12px;">
            <strong>Part of:</strong>
            @foreach($ancestors as $anc)
                {{ $anc['title'] ?? '[Untitled]' }}@if(!$loop->last) &raquo; @endif
            @endforeach
        </div>
    @endif

    <h1>{{ $entity['properties']['rico:title'][0]['value'] ?? '[Untitled]' }}</h1>

    <h2 class="section-heading">Identity area</h2>

    @foreach($isadg ?? [] as $area => $fields)
        @if(!empty($fields))
            @foreach($fields as $label => $value)
                @if(!empty($value))
                    <div class="field-row">
                        <div class="field-label">{{ $label }}</div>
                        <div class="field-value">{!! nl2br(e($value)) !!}</div>
                    </div>
                @endif
            @endforeach
        @endif
    @endforeach

    @if(!empty($children))
        <h2 class="section-heading">Child records</h2>
        <ul>
            @foreach($children as $child)
                <li>{{ $child['title'] ?? '[Untitled]' }}</li>
            @endforeach
        </ul>
    @endif
@endsection
