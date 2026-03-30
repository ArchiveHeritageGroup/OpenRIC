@extends('theme::layouts.1col')

@section('title', 'Map Columns')
@section('body-class', 'admin data-migration map')

@section('content')
  <div class="multiline-header d-flex align-items-center mb-3">
    <i class="fas fa-3x fa-columns me-3" aria-hidden="true"></i>
    <div class="d-flex flex-column"><h1 class="mb-0">Map Columns</h1><span class="small text-muted">{{ $fileName }} &middot; {{ number_format($totalRows) }} rows &middot; Target: {{ ucfirst($targetType) }}</span></div>
    <div class="ms-auto"><a href="{{ route('data-migration.upload') }}" class="btn atom-btn-white"><i class="fas fa-arrow-left me-1"></i> Back</a></div>
  </div>

  <form method="post" action="{{ route('data-migration.preview') }}" id="mappingForm">
    @csrf
    <div class="card mb-4">
      <div class="card-header d-flex justify-content-between" style="background:var(--bs-primary, #0d6efd);color:#fff">
        <strong>Column Mapping</strong>
        @if(!empty($savedMappings))
        <select id="loadSavedMapping" class="form-select form-select-sm w-auto">
          <option value="">Load saved mapping...</option>
          @foreach($savedMappings as $m)
            <option value="{{ $m['id'] }}">{{ $m['name'] ?? '' }}</option>
          @endforeach
        </select>
        @endif
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-bordered mb-0">
            <thead><tr><th>Source Column</th><th>Target Field</th><th>Sample Data</th></tr></thead>
            <tbody>
              @foreach($sourceColumns as $idx => $col)
                <tr>
                  <td><strong>{{ $col }}</strong></td>
                  <td>
                    <select name="mapping[{{ $col }}]" class="form-select form-select-sm mapping-select">
                      <option value="">-- Skip --</option>
                      @foreach($targetFields as $fieldKey => $fieldLabel)
                        <option value="{{ $fieldKey }}">{{ $fieldLabel }}</option>
                      @endforeach
                    </select>
                  </td>
                  <td><small class="text-muted">{{ $previewRows[0][$col] ?? '' }}</small></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2">
      <button type="submit" class="btn atom-btn-outline-success"><i class="fas fa-eye me-1"></i> Preview</button>
      <button type="button" class="btn atom-btn-white" id="saveMappingBtn"><i class="fas fa-save me-1"></i> Save Mapping</button>
      <a href="{{ route('data-migration.upload') }}" class="btn atom-btn-white">Cancel</a>
    </div>

    <input type="hidden" name="column_mapping" id="columnMappingJson">
  </form>

  {{-- Save Mapping Modal --}}
  <div class="modal fade" id="saveMappingModal" tabindex="-1">
    <div class="modal-dialog"><div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Save Mapping</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Name <span class="badge bg-danger ms-1">Required</span></label><input type="text" id="mappingName" class="form-control" required></div>
        <div class="mb-3"><label class="form-label">Category</label><input type="text" id="mappingCategory" class="form-control" value="Custom"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn atom-btn-white" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn atom-btn-outline-success" id="confirmSaveMapping">Save</button></div>
    </div></div>
  </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('mappingForm');
    form.addEventListener('submit', function() {
        var mapping = {};
        document.querySelectorAll('.mapping-select').forEach(function(sel) {
            var source = sel.name.match(/\[(.+)\]/)[1];
            if (sel.value) mapping[source] = sel.value;
        });
        document.getElementById('columnMappingJson').value = JSON.stringify(mapping);
    });

    document.getElementById('saveMappingBtn').addEventListener('click', function() {
        new bootstrap.Modal(document.getElementById('saveMappingModal')).show();
    });

    document.getElementById('confirmSaveMapping').addEventListener('click', function() {
        var name = document.getElementById('mappingName').value;
        if (!name) { alert('Name is required'); return; }
        var mapping = {};
        document.querySelectorAll('.mapping-select').forEach(function(sel) {
            var source = sel.name.match(/\[(.+)\]/)[1];
            if (sel.value) mapping[source] = sel.value;
        });
        fetch('{{ route("data-migration.save-mapping") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ name: name, category: document.getElementById('mappingCategory').value, field_mappings: mapping }),
        }).then(function(r) { return r.json(); }).then(function(data) {
            if (data.success) { bootstrap.Modal.getInstance(document.getElementById('saveMappingModal')).hide(); alert('Mapping saved!'); }
        });
    });

    var loadSelect = document.getElementById('loadSavedMapping');
    if (loadSelect) {
        loadSelect.addEventListener('change', function() {
            if (!this.value) return;
            fetch('{{ route("data-migration.load-mapping") }}?id=' + this.value)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.column_mapping) {
                        var mapping = data.column_mapping;
                        document.querySelectorAll('.mapping-select').forEach(function(sel) {
                            var source = sel.name.match(/\[(.+)\]/)[1];
                            sel.value = mapping[source] || '';
                        });
                    }
                });
        });
    }
});
</script>
@endpush
