@extends('theme::layouts.1col')
@section('title', 'Access Denied')
@section('content')
<div class="container"><div class="row justify-content-center mt-5"><div class="col-md-6"><div class="card border-danger"><div class="card-header bg-danger text-white"><h4 class="mb-0"><i class="bi bi-x-circle"></i> Access Denied</h4></div><div class="card-body text-center"><i class="bi bi-lock fs-1 text-danger mb-4"></i><h5>You do not have permission to access this resource.</h5>@if($classification ?? null)<p class="mt-3"><strong>Required Classification:</strong> <span class="badge" style="background-color:{{ $classification->color ?? '#666' }}">{{ e($classification->name ?? '') }}</span></p>@endif<hr><div class="d-grid gap-2"><a href="javascript:history.back()" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Go Back</a></div></div></div></div></div></div>
@endsection
