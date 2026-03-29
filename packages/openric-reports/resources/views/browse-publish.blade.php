@extends('theme::layouts.1col')
@section('title', 'Publish Management')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-eye me-2"></i>Publish Management</h1>
      <a href="{{ route('reports.browse') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>
    <p class="text-muted">Manage publication status of archival descriptions.</p>

    <div class="card">
      <div class="card-body">
        @if($items->count() > 0)
          <table class="table table-bordered table-sm table-striped">
            <thead><tr><th>#</th><th>Identifier</th><th>Title</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              @foreach($items as $item)
              <tr>
                <td>{{ $item->id ?? '' }}</td>
                <td>{{ $item->identifier ?? '' }}</td>
                <td>{{ $item->title ?? '' }}</td>
                <td>{{ $item->publication_status ?? 'draft' }}</td>
                <td><a href="#" class="btn btn-sm btn-outline-primary">Edit</a></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        @else
          <p class="text-muted text-center py-4">No items to display. Use Browse to filter records first.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
