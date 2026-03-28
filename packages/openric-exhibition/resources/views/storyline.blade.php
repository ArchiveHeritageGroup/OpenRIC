@extends('theme::layouts.1col')

@section('title', $storyline->title . ' — ' . $exhibition->title)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-stream me-2"></i>{{ $storyline->title }}</h1>
        <a href="{{ route('exhibition.storylines', $exhibition->id) }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    <p class="text-muted">Exhibition: {{ $exhibition->title }}</p>

    <div class="card">
        <div class="card-header">Storyline Details</div>
        <div class="card-body">
            <div class="row mb-2">
                <div class="col-sm-3 fw-bold">Title</div>
                <div class="col-sm-9">{{ $storyline->title }}</div>
            </div>
            @if (!empty($storyline->description))
            <div class="row mb-2">
                <div class="col-sm-3 fw-bold">Description</div>
                <div class="col-sm-9">{!! nl2br(e($storyline->description)) !!}</div>
            </div>
            @endif
            <div class="row mb-2">
                <div class="col-sm-3 fw-bold">Sort Order</div>
                <div class="col-sm-9">{{ $storyline->sort_order ?? 0 }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
