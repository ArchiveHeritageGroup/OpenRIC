@extends('theme::layouts.1col')
@section('title', 'Report Templates')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-copy me-2"></i>Report Templates</h1>
      <a href="{{ route('reports.builder.index') }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="card">
      <div class="card-header bg-primary text-white">Available Templates</div>
      <div class="table-responsive">
        <table class="table table-bordered table-sm mb-0">
          <thead><tr><th>Name</th><th>Description</th><th>Category</th><th>Updated</th><th>Actions</th></tr></thead>
          <tbody>
            @forelse($templates ?? collect() as $t)
            <tr>
              <td>{{ $t->name ?? '' }}</td>
              <td>{{ Str::limit($t->description ?? '', 60) }}</td>
              <td>{{ $t->category ?? '' }}</td>
              <td>{{ $t->updated_at ?? '' }}</td>
              <td>
                <div class="btn-group btn-group-sm">
                  <a href="{{ route('reports.builder.preview-template', $t->id) }}" class="btn btn-outline-primary"><i class="fas fa-eye"></i></a>
                  <a href="{{ route('reports.builder.edit-template', $t->id) }}" class="btn btn-outline-secondary"><i class="fas fa-pencil-alt"></i></a>
                  <form method="post" action="{{ route('reports.builder.delete-template', $t->id) }}" style="display:inline" onsubmit="return confirm('Delete this template?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-outline-danger"><i class="fas fa-trash"></i></button>
                  </form>
                </div>
              </td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-muted text-center py-3">No templates available.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
