@extends('theme::layouts.2col')

@section('title', $tree['title'] ?? 'Tree View')

@section('sidebar')
    @include('theme::partials.sidebar')
@endsection

@section('content')
    {{-- Breadcrumb from ancestors --}}
    <nav aria-label="Hierarchy breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('hierarchy.index') }}">Hierarchy</a></li>
            @foreach($ancestors as $ancestor)
                <li class="breadcrumb-item">
                    <a href="{{ route('hierarchy.tree', ['iri' => urlencode($ancestor['iri'])]) }}">{{ $ancestor['title'] }}</a>
                </li>
            @endforeach
            <li class="breadcrumb-item active" aria-current="page">{{ $tree['title'] ?? 'Current' }}</li>
        </ol>
    </nav>

    <h1 class="h3 mb-4">{{ $tree['title'] ?? 'Untitled' }}</h1>

    @if(!empty($tree['children']))
        @include('record-manage::hierarchy._tree_level', ['nodes' => $tree['children'], 'depth' => 0])
    @else
        <p class="text-muted">No sub-levels found.</p>
    @endif
@endsection
