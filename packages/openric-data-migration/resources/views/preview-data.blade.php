@extends('theme::layouts.1col')

@section('title', 'Preview Data')
@section('body-class', 'admin data-migration preview-data')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-table me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Preview Data</h1><span class="small text-muted">Data Migration</span></div>
  </div>

  @if(isset($rows) && count($rows))
    <div class="table-responsive"><table class="table table-bordered table-hover mb-0">
      <thead><tr>@foreach(array_keys((array)($rows->first() ?? [])) as $h)<th>{{ $h }}</th>@endforeach</tr></thead>
      <tbody>@foreach($rows as $row)<tr>@foreach((array)$row as $v)<td><small>{{ $v }}</small></td>@endforeach</tr>@endforeach</tbody>
    </table></div>
  @else
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>No data available for preview.</div>
  @endif
@endsection
