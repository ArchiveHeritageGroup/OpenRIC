@extends('theme::layouts.1col')
@section('title', 'Query Builder')
@section('body-class', 'admin reports')

@section('content')
<div class="row">
  <div class="col-md-3">@include('reports::_menu')</div>
  <div class="col-md-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1><i class="fas fa-database me-2"></i>Query Builder</h1>
      @if(isset($report))
      <a href="{{ route('reports.builder.edit', $report->id) }}" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Back to Edit</a>
      @endif
    </div>

    @if(isset($report))
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">{{ $report->name ?? 'Report' }} -- Query Configuration</div>
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Table</label>
            <select id="queryTable" class="form-select">
              <option value="">-- Select table --</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Columns</label>
            <div id="queryColumns" class="border rounded p-2" style="max-height:200px;overflow-y:auto"><small class="text-muted">Select a table first</small></div>
          </div>
          <div class="col-md-4">
            <label class="form-label">Order By</label>
            <select id="queryOrderBy" class="form-select mb-2"><option value="">-- None --</option></select>
            <select id="queryOrderDir" class="form-select">
              <option value="asc">Ascending</option>
              <option value="desc">Descending</option>
            </select>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Limit</label>
          <input type="number" id="queryLimit" class="form-control" value="100" min="1" max="10000">
        </div>
        <div class="d-flex gap-2">
          <button id="executeQueryBtn" class="btn btn-primary btn-sm"><i class="fas fa-play me-1"></i>Execute</button>
          <button id="saveQueryBtn" class="btn btn-success btn-sm"><i class="fas fa-save me-1"></i>Save Query</button>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header bg-primary text-white"><i class="fas fa-table me-2"></i>Results</div>
      <div class="card-body" id="queryResults">
        <p class="text-muted text-center">Configure and execute a query above.</p>
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
    var tableSelect = document.getElementById('queryTable');
    var colContainer = document.getElementById('queryColumns');
    var orderBySelect = document.getElementById('queryOrderBy');

    // Load tables
    fetch('{{ route("reports.api.tables") }}')
    .then(r => r.json())
    .then(data => {
        if (data.tables) {
            data.tables.forEach(t => {
                var opt = document.createElement('option');
                opt.value = t; opt.textContent = t;
                tableSelect.appendChild(opt);
            });
        }
    });

    // Load columns on table change
    tableSelect.addEventListener('change', function() {
        if (!this.value) return;
        fetch('{{ route("reports.api.columns") }}?table=' + this.value)
        .then(r => r.json())
        .then(data => {
            if (data.columns) {
                colContainer.innerHTML = '';
                orderBySelect.innerHTML = '<option value="">-- None --</option>';
                data.columns.forEach(c => {
                    var div = document.createElement('div');
                    div.className = 'form-check';
                    div.innerHTML = '<input class="form-check-input col-check" type="checkbox" value="' + c.name + '" id="col_' + c.name + '" checked><label class="form-check-label" for="col_' + c.name + '">' + c.name + ' <small class="text-muted">(' + c.type + ')</small></label>';
                    colContainer.appendChild(div);
                    var opt = document.createElement('option');
                    opt.value = c.name; opt.textContent = c.name;
                    orderBySelect.appendChild(opt);
                });
            }
        });
    });

    // Execute query
    document.getElementById('executeQueryBtn').addEventListener('click', function() {
        var cols = Array.from(document.querySelectorAll('.col-check:checked')).map(c => c.value);
        var query = {
            table: tableSelect.value,
            columns: cols.length > 0 ? cols : undefined,
            orderBy: orderBySelect.value || undefined,
            orderDir: document.getElementById('queryOrderDir').value,
            limit: parseInt(document.getElementById('queryLimit').value) || 100
        };

        fetch('{{ route("reports.api.query.execute") }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({query: query})
        })
        .then(r => r.json())
        .then(data => {
            var el = document.getElementById('queryResults');
            if (data.success && data.data && data.data.length > 0) {
                var keys = Object.keys(data.data[0]);
                var html = '<p><strong>' + data.count + '</strong> rows.</p><div class="table-responsive"><table class="table table-bordered table-sm table-striped"><thead><tr>';
                keys.forEach(k => html += '<th>' + k + '</th>');
                html += '</tr></thead><tbody>';
                data.data.forEach(row => {
                    html += '<tr>';
                    keys.forEach(k => html += '<td>' + (row[k] !== null ? row[k] : '') + '</td>');
                    html += '</tr>';
                });
                html += '</tbody></table></div>';
                el.innerHTML = html;
            } else {
                el.innerHTML = '<div class="alert alert-info">' + (data.error || 'No results.') + '</div>';
            }
        });
    });

    // Save query
    document.getElementById('saveQueryBtn').addEventListener('click', function() {
        var cols = Array.from(document.querySelectorAll('.col-check:checked')).map(c => c.value);
        var query = {
            table: tableSelect.value,
            columns: cols.length > 0 ? cols : undefined,
            orderBy: orderBySelect.value || undefined,
            orderDir: document.getElementById('queryOrderDir').value,
            limit: parseInt(document.getElementById('queryLimit').value) || 100
        };

        fetch('{{ route("reports.api.query.save", $report->id) }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({query: query})
        })
        .then(r => r.json())
        .then(data => {
            alert(data.success ? 'Query saved.' : 'Error: ' + (data.error || 'Unknown'));
        });
    });
});
</script>
@endpush
@endif
@endsection
