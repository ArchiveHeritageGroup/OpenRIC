@extends('theme::layouts.1col')
@section('title', 'Report Widgets')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-th me-2"></i>Report Widgets</h1>
      @if(isset($report))
      <a href="{{ route('reports.builder.edit', $report->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back</a>
      @endif
    </div>

    @if(isset($report))
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">{{ $report->name ?? 'Report' }} -- Widget Configuration</div>
      <div class="card-body">
        <p class="text-muted">Configure dashboard widgets for this report. Widgets can display charts, tables, or KPI metrics.</p>
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Widget Type</label>
            <select id="widgetType" class="form-select">
              <option value="chart">Chart</option>
              <option value="table">Table</option>
              <option value="kpi">KPI Metric</option>
              <option value="text">Text Block</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Title</label>
            <input type="text" id="widgetTitle" class="form-control" placeholder="Widget title">
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <button id="addWidgetBtn" class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Add Widget</button>
          </div>
        </div>
        <div id="widgetList"></div>
      </div>
    </div>
    @else
    <div class="alert alert-warning">Report not found.</div>
    @endif
  </div>
</div>

@if(isset($report))
@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function loadWidgets() {
        fetch('{{ route("reports.api.widgets", $report->id) }}')
        .then(r => r.json())
        .then(data => {
            var el = document.getElementById('widgetList');
            if (data.widgets && data.widgets.length > 0) {
                var html = '<div class="row">';
                data.widgets.forEach(w => {
                    html += '<div class="col-md-6 mb-3"><div class="card"><div class="card-header d-flex justify-content-between"><span>' + (w.title || 'Widget') + '</span><span class="badge bg-secondary">' + w.widget_type + '</span></div><div class="card-body"><small class="text-muted">Sort order: ' + w.sort_order + '</small></div></div></div>';
                });
                html += '</div>';
                el.innerHTML = html;
            } else {
                el.innerHTML = '<p class="text-muted">No widgets configured.</p>';
            }
        });
    }
    loadWidgets();

    document.getElementById('addWidgetBtn')?.addEventListener('click', function() {
        fetch('{{ route("reports.api.widget.save", $report->id) }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({
                widget_type: document.getElementById('widgetType').value,
                title: document.getElementById('widgetTitle').value
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) loadWidgets();
            else alert(data.error || 'Failed to add widget');
        });
    });
});
</script>
@endpush
@endif
@endsection
