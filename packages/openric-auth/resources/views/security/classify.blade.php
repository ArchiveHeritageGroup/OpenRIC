@extends('theme::layouts.1col')
@section('title', ($currentClassification ?? null) ? 'Reclassify Record' : 'Classify Record')
@section('content')
<h1 class="multiline">{{ ($currentClassification ?? null) ? 'Reclassify record' : 'Classify record' }} <span class="sub">{{ $resource->title ?? '' }}</span></h1>
<section id="content">
<div class="alert alert-light border mb-4"><h6 class="alert-heading">{{ $resource->title ?? '' }}</h6>@if($currentClassification ?? null)<hr class="my-2"><small><strong>Current:</strong> <span class="badge" style="background-color:{{ $currentClassification->classification_color ?? '#666' }};">{{ $currentClassification->classification_name ?? '' }}</span></small>@endif</div>
<form method="post" action="{{ route('acl.classify-store') }}">@csrf<input type="hidden" name="object_iri" value="{{ $resource->iri ?? '' }}">
<fieldset class="mb-4"><legend class="h6 border-bottom pb-2 mb-3"><i class="bi bi-lock me-2"></i>Classification Level</legend><div class="row">@foreach($classifications ?? [] as $c)<div class="col-md-4 mb-3"><div class="form-check card h-100"><div class="card-body"><input class="form-check-input" type="radio" name="classification_id" id="cls_{{ $c->id }}" value="{{ $c->id }}" required><label class="form-check-label w-100" for="cls_{{ $c->id }}"><span class="badge w-100 py-2 mb-2" style="background-color:{{ $c->color }};">{{ $c->name }}</span><small class="d-block text-muted">Level {{ $c->level }}</small></label></div></div></div>@endforeach</div></fieldset>
<fieldset class="mb-4"><legend class="h6 border-bottom pb-2 mb-3">Classification Details</legend><div class="mb-3"><label for="reason" class="form-label">Reason</label><textarea name="reason" id="reason" class="form-control" rows="3" placeholder="Justification..."></textarea></div></fieldset>
<section class="actions"><ul class="list-unstyled d-flex gap-2"><li><a href="javascript:history.back()" class="btn btn-secondary">Cancel</a></li><li><button class="btn btn-primary" type="submit">Classify</button></li></ul></section>
</form></section>
@endsection
