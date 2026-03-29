@extends('theme::layouts.1col')
@section('title', 'Audit Physical Storage')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    @include('reports::_audit-table', ['auditTitle' => 'Audit Physical Storage'])
  </div>
</div>
@endsection
