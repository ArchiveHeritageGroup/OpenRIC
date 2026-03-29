@extends('theme::layouts.1col')
@section('title', 'Schedule Report')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-calendar me-2"></i>Schedule Report</h1>
      @if(isset($report))
      <a href="{{ route('reports.builder.edit', $report->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
      @endif
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    @if(isset($report))
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">{{ $report->name ?? 'Report' }} -- Schedule Configuration</div>
      <div class="card-body">
        <form method="post" action="{{ route('reports.builder.schedule-store', $report->id) }}">
          @csrf
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Frequency <span class="text-danger">*</span></label>
              <select name="frequency" class="form-select" required>
                <option value="daily" {{ ($existingSchedule->frequency ?? '') === 'daily' ? 'selected' : '' }}>Daily</option>
                <option value="weekly" {{ ($existingSchedule->frequency ?? '') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                <option value="monthly" {{ ($existingSchedule->frequency ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Time <span class="text-danger">*</span></label>
              <input type="time" name="time" class="form-control" required value="{{ $existingSchedule->time ?? '08:00' }}">
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Format</label>
              <select name="format" class="form-select">
                <option value="csv" {{ ($existingSchedule->format ?? '') === 'csv' ? 'selected' : '' }}>CSV</option>
                <option value="pdf" {{ ($existingSchedule->format ?? '') === 'pdf' ? 'selected' : '' }}>PDF</option>
                <option value="xlsx" {{ ($existingSchedule->format ?? '') === 'xlsx' ? 'selected' : '' }}>XLSX</option>
              </select>
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Day of Week (for weekly)</label>
              <select name="day_of_week" class="form-select">
                @foreach(['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $i => $day)
                <option value="{{ $i }}" {{ ($existingSchedule->day_of_week ?? '') == $i ? 'selected' : '' }}>{{ $day }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Day of Month (for monthly)</label>
              <input type="number" name="day_of_month" class="form-control" min="1" max="31" value="{{ $existingSchedule->day_of_month ?? 1 }}">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Email Recipients (comma-separated)</label>
            <input type="text" name="email_recipients" class="form-control" value="{{ $existingSchedule->email_recipients ?? '' }}" placeholder="user@example.com, admin@example.com">
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Schedule</button>
        </form>
      </div>
    </div>
    @else
    <div class="alert alert-warning">Report not found.</div>
    @endif
  </div>
</div>
@endsection
