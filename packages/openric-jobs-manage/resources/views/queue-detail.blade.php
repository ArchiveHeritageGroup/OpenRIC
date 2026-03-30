@extends('theme::layouts.1col')
@section('title', 'Queue Details')
@section('content')
<div class="d-flex align-items-center mb-3"><h1 class="h3 mb-0"><i class="fas fa-eye me-2"></i>Queue Details</h1></div>
<div class="row"><div class="col-lg-8"><div class="card mb-4"><div class="card-header fw-semibold">Details</div><div class="card-body"><dl class="row mb-0"><dt class="col-sm-4">Job ID</dt><dd class="col-sm-8">{{ $record->id ?? '-' }}</dd><dt class="col-sm-4">Queue</dt><dd class="col-sm-8">{{ $record->queue ?? '-' }}</dd><dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ $record->status ?? '-' }}</dd></dl></div></div></div>
<div class="col-lg-4"><a href="{{ route('jobs.index') }}" class="btn btn-outline-secondary w-100"><i class="fas fa-arrow-left me-1"></i>Back</a></div></div>
@endsection
