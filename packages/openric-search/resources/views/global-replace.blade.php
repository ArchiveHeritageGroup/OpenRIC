@extends('theme::layouts.1col')

@section('title', 'Global search/replace')
@section('body-class', 'search global-replace')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-exchange-alt me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column">
      <h1 class="mb-0">Global search/replace</h1>
      <span class="small text-muted">Find and replace text across description fields</span>
    </div>
  </div>

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

  <form action="{{ route('search.globalReplace') }}" method="post" class="card mb-4">
    @csrf
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-4">
          <label for="column" class="form-label">Column <span class="badge bg-danger ms-1">Required</span></label>
          <select name="column" id="column" class="form-select" required>
            <option value="">-- Select a field --</option>
            @foreach($columns as $value => $label)
              <option value="{{ $value }}" {{ (old('column', $column ?? '') === $value) ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
          @error('column')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label for="pattern" class="form-label">Search pattern <span class="badge bg-danger ms-1">Required</span></label>
          <input type="text" name="pattern" id="pattern" class="form-control" value="{{ old('pattern', $pattern ?? '') }}" required>
          @error('pattern')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-4">
          <label for="replacement" class="form-label">Replacement</label>
          <input type="text" name="replacement" id="replacement" class="form-control" value="{{ old('replacement', $replacement ?? '') }}">
        </div>
        <div class="col-md-4">
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="caseSensitive" id="caseSensitive" value="1" {{ old('caseSensitive', $caseSensitive ?? true) ? 'checked' : '' }}>
            <label class="form-check-label" for="caseSensitive">Case sensitive</label>
          </div>
        </div>
        <div class="col-12">
          <button type="submit" class="btn atom-btn-outline-success"><i class="fas fa-search"></i> Preview</button>
          <a href="{{ route('search.globalReplace') }}" class="btn atom-btn-white ms-2"><i class="fas fa-undo"></i> Reset</a>
        </div>
      </div>
    </div>
  </form>

  @if(isset($results) && $results !== null)
    @if($results->count() > 0)
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle"></i>
        <strong>Warning:</strong> This will permanently modify <strong>{{ number_format($count) }}</strong> record(s). This action cannot be undone!
      </div>

      <table class="table table-bordered table-striped table-hover mb-4">
        <thead><tr><th style="width: 20%">Title</th><th style="width: 40%">Current value</th><th style="width: 40%">New value</th></tr></thead>
        <tbody>
          @foreach($results as $row)
            <tr>
              <td>{{ $row->title }}</td>
              <td class="text-break small"><span class="text-danger">{!! e($row->current_value) !!}</span></td>
              <td class="text-break small"><span class="text-success">{!! e($row->new_value) !!}</span></td>
            </tr>
          @endforeach
        </tbody>
      </table>

      @if($count > $results->count())
        <div class="alert alert-info mb-3"><i class="fas fa-info-circle"></i> Showing {{ $results->count() }} of {{ number_format($count) }} affected record(s).</div>
      @endif

      <form action="{{ route('search.globalReplace') }}" method="post">
        @csrf
        <input type="hidden" name="column" value="{{ $column }}">
        <input type="hidden" name="pattern" value="{{ $pattern }}">
        <input type="hidden" name="replacement" value="{{ $replacement }}">
        <input type="hidden" name="caseSensitive" value="{{ $caseSensitive ? '1' : '0' }}">
        <input type="hidden" name="confirm" value="1">
        <div class="d-flex gap-2">
          <button type="submit" class="btn atom-btn-outline-danger" onclick="return confirm('Are you sure? This will modify {{ number_format($count) }} record(s) and cannot be undone.')">
            <i class="fas fa-check"></i> Confirm replacement ({{ number_format($count) }} records)
          </button>
          <a href="{{ route('search.globalReplace') }}" class="btn atom-btn-white"><i class="fas fa-times"></i> Cancel</a>
        </div>
      </form>
    @else
      <div class="alert alert-info"><i class="fas fa-info-circle"></i> No records found matching the search pattern.</div>
    @endif
  @endif
@endsection
