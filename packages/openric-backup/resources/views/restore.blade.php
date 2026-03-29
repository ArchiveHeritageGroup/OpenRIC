@extends('theme::layouts.1col')
@section('title', 'Restore from Backup')
@section('body-class', 'admin backup restore')

@section('content')
<div class="multiline-header d-flex align-items-center mb-3">
  <i class="fas fa-3x fa-undo me-3" aria-hidden="true"></i>
  <div class="d-flex flex-column">
    <h1 class="mb-0">Restore from Backup</h1>
    <span class="small text-muted">Restore PostgreSQL database, Fuseki triplestore, or files from an existing backup</span>
  </div>
</div>

<div class="mb-3">
  <a href="{{ route('backups.index') }}" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i> Back to Backups
  </a>
</div>

<div class="alert alert-warning">
  <i class="fas fa-exclamation-triangle me-1"></i>
  <strong>Warning:</strong> Restoring will overwrite existing data. This action cannot be undone. Ensure you have a current backup before proceeding.
</div>

@if(count($backups) > 0)
<div class="card mb-4">
  <div class="card-header fw-semibold" style="background:var(--openric-primary, #2c3e50);color:#fff">
    <i class="fas fa-file-archive me-1"></i> Select Backup
  </div>
  <div class="card-body">
    <div class="mb-3">
      <label for="backup-select" class="form-label">Available Backups</label>
      <select class="form-select" id="backup-select" onchange="onBackupSelected()">
        <option value="">-- Select a backup --</option>
        @foreach($backups as $backup)
          @if($backup['status'] === 'completed')
            <option value="{{ $backup['id'] }}"
                    data-components="{{ implode(',', $backup['components_array'] ?? []) }}"
                    data-filename="{{ $backup['filename'] }}"
                    data-size="{{ $backup['size_human'] }}"
                    data-date="{{ $backup['completed_at'] ?? $backup['created_at'] }}"
                    data-type="{{ $backup['type'] }}">
              {{ $backup['completed_at'] ?? $backup['created_at'] }} - {{ $backup['filename'] }} ({{ $backup['size_human'] }})
            </option>
          @endif
        @endforeach
      </select>
    </div>

    {{-- Backup details --}}
    <div id="backup-details" class="d-none">
      <div class="card bg-light mb-3">
        <div class="card-body">
          <h6 class="card-title"><i class="fas fa-info-circle me-1"></i> Backup Details</h6>
          <table class="table table-sm table-borderless mb-0">
            <tr>
              <td class="text-muted" style="width:100px;">File</td>
              <td id="detail-filename"></td>
            </tr>
            <tr>
              <td class="text-muted">Date</td>
              <td id="detail-date"></td>
            </tr>
            <tr>
              <td class="text-muted">Type</td>
              <td id="detail-type"></td>
            </tr>
            <tr>
              <td class="text-muted">Size</td>
              <td id="detail-size"></td>
            </tr>
            <tr>
              <td class="text-muted">Contains</td>
              <td id="detail-components"></td>
            </tr>
          </table>
        </div>
      </div>

      <h6><i class="fas fa-check-square me-1"></i> Select Components to Restore</h6>
      <div class="mb-3">
        <div class="form-check mb-2" id="restore-comp-database-wrap" style="display:none;">
          <input class="form-check-input restore-component" type="checkbox" id="restore-comp-database" value="database">
          <label class="form-check-label" for="restore-comp-database">
            <i class="fas fa-database text-primary me-1"></i> PostgreSQL Database
          </label>
          <div class="form-text">Restore the full PostgreSQL database from the pg_dump backup.</div>
        </div>
        <div class="form-check mb-2" id="restore-comp-triplestore-wrap" style="display:none;">
          <input class="form-check-input restore-component" type="checkbox" id="restore-comp-triplestore" value="triplestore">
          <label class="form-check-label" for="restore-comp-triplestore">
            <i class="fas fa-project-diagram text-info me-1"></i> Fuseki Triplestore
          </label>
          <div class="form-text">Restore the RiC-O graph data from the N-Quads backup.</div>
        </div>
        <div class="form-check mb-2" id="restore-comp-uploads-wrap" style="display:none;">
          <input class="form-check-input restore-component" type="checkbox" id="restore-comp-uploads" value="uploads">
          <label class="form-check-label" for="restore-comp-uploads">
            <i class="fas fa-upload text-warning me-1"></i> Uploads
          </label>
          <div class="form-text">Restore uploaded digital objects and media files.</div>
        </div>
        <div class="form-check mb-2" id="restore-comp-packages-wrap" style="display:none;">
          <input class="form-check-input restore-component" type="checkbox" id="restore-comp-packages" value="packages">
          <label class="form-check-label" for="restore-comp-packages">
            <i class="fas fa-puzzle-piece text-secondary me-1"></i> Packages
          </label>
          <div class="form-text">Restore all packages from the backup.</div>
        </div>
        <div class="form-check mb-2" id="restore-comp-framework-wrap" style="display:none;">
          <input class="form-check-input restore-component" type="checkbox" id="restore-comp-framework" value="framework">
          <label class="form-check-label" for="restore-comp-framework">
            <i class="fas fa-code text-dark me-1"></i> Framework
          </label>
          <div class="form-text">Restore application framework files.</div>
        </div>
      </div>

      {{-- Progress --}}
      <div id="restore-progress" class="d-none mb-3">
        <div class="progress mb-2">
          <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="restore-progress-bar">0%</div>
        </div>
        <div id="restore-status" class="small text-muted"></div>
      </div>

      {{-- Result --}}
      <div id="restore-result" class="d-none mb-3"></div>

      <button type="button" class="btn btn-danger" id="btn-start-restore" onclick="confirmRestore()" disabled>
        <i class="fas fa-undo me-1"></i> Restore Selected Components
      </button>
    </div>
  </div>
</div>
@else
  <div class="card">
    <div class="card-body text-center py-5 text-muted">
      <i class="fas fa-3x fa-box-open mb-3 d-block"></i>
      <p class="mb-2">No completed backups available for restore.</p>
      <a href="{{ route('backups.index') }}" class="btn btn-outline-success">
        <i class="fas fa-plus me-1"></i> Create a Backup First
      </a>
    </div>
  </div>
@endif

@endsection

@push('js')
<script>
var allComponents = ['database', 'triplestore', 'uploads', 'packages', 'framework'];

function onBackupSelected() {
  var select = document.getElementById('backup-select');
  var details = document.getElementById('backup-details');
  var option = select.options[select.selectedIndex];

  if (!option.value) {
    details.classList.add('d-none');
    return;
  }

  details.classList.remove('d-none');

  document.getElementById('detail-filename').textContent = option.dataset.filename;
  document.getElementById('detail-date').textContent = option.dataset.date;
  document.getElementById('detail-type').textContent = option.dataset.type;
  document.getElementById('detail-size').textContent = option.dataset.size;

  var components = option.dataset.components ? option.dataset.components.split(',') : [];
  var compHtml = '';
  components.forEach(function(c) {
    switch (c) {
      case 'database':
        compHtml += '<span class="badge bg-primary me-1"><i class="fas fa-database"></i> DB</span>';
        break;
      case 'triplestore':
        compHtml += '<span class="badge bg-info me-1"><i class="fas fa-project-diagram"></i> Triplestore</span>';
        break;
      case 'uploads':
        compHtml += '<span class="badge bg-warning text-dark me-1"><i class="fas fa-upload"></i> Uploads</span>';
        break;
      case 'packages':
        compHtml += '<span class="badge bg-secondary me-1"><i class="fas fa-puzzle-piece"></i> Packages</span>';
        break;
      case 'framework':
        compHtml += '<span class="badge bg-dark me-1"><i class="fas fa-code"></i> Framework</span>';
        break;
    }
  });
  document.getElementById('detail-components').innerHTML = compHtml;

  // Show/hide component checkboxes based on what the backup contains
  allComponents.forEach(function(comp) {
    var wrap = document.getElementById('restore-comp-' + comp + '-wrap');
    var cb = document.getElementById('restore-comp-' + comp);
    if (components.indexOf(comp) !== -1) {
      wrap.style.display = '';
      cb.checked = false;
    } else {
      wrap.style.display = 'none';
      cb.checked = false;
    }
  });

  updateRestoreButton();
}

document.querySelectorAll('.restore-component').forEach(function(cb) {
  cb.addEventListener('change', updateRestoreButton);
});

function updateRestoreButton() {
  var checked = document.querySelectorAll('.restore-component:checked');
  document.getElementById('btn-start-restore').disabled = (checked.length === 0);
}

function confirmRestore() {
  var checked = [];
  document.querySelectorAll('.restore-component:checked').forEach(function(cb) {
    checked.push(cb.value);
  });

  if (checked.length === 0) {
    alert('Please select at least one component to restore.');
    return;
  }

  var msg = 'You are about to restore the following components:\n\n' +
    checked.join(', ').toUpperCase() +
    '\n\nThis will OVERWRITE existing data and CANNOT be undone.\n\nAre you sure you want to proceed?';

  if (!confirm(msg)) {
    return;
  }

  startRestore(checked);
}

function startRestore(components) {
  var backupId = document.getElementById('backup-select').value;
  var progressDiv = document.getElementById('restore-progress');
  var progressBar = document.getElementById('restore-progress-bar');
  var statusDiv = document.getElementById('restore-status');
  var resultDiv = document.getElementById('restore-result');
  var restoreBtn = document.getElementById('btn-start-restore');

  progressDiv.classList.remove('d-none');
  resultDiv.classList.add('d-none');
  resultDiv.innerHTML = '';
  restoreBtn.disabled = true;

  // Simulate progress
  var progress = 0;
  var interval = setInterval(function() {
    progress += Math.random() * 10;
    if (progress > 85) progress = 85;
    progressBar.style.width = Math.round(progress) + '%';
    progressBar.textContent = Math.round(progress) + '%';
  }, 800);

  statusDiv.textContent = 'Restoring: ' + components.join(', ') + '...';

  fetch('{{ route("backups.doRestore") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ backup_id: parseInt(backupId), components: components }),
  })
  .then(function(response) { return response.json(); })
  .then(function(data) {
    clearInterval(interval);
    progressBar.style.width = '100%';
    progressBar.textContent = '100%';
    progressBar.classList.remove('progress-bar-animated');

    if (data.success) {
      progressBar.classList.add('bg-success');
      statusDiv.textContent = data.message;

      var html = '<div class="alert alert-success"><strong>Restore completed!</strong>';
      if (data.restored && data.restored.length > 0) {
        html += '<ul class="mb-0 mt-1">';
        data.restored.forEach(function(r) { html += '<li>Restored: ' + r + '</li>'; });
        html += '</ul>';
      }
      if (data.errors && data.errors.length > 0) {
        html += '<hr><strong>Warnings:</strong><ul class="mb-0">';
        data.errors.forEach(function(e) { html += '<li class="text-warning">' + e + '</li>'; });
        html += '</ul>';
      }
      html += '</div>';
      resultDiv.innerHTML = html;
      resultDiv.classList.remove('d-none');
    } else {
      progressBar.classList.add('bg-danger');
      statusDiv.textContent = 'Restore failed.';

      var html = '<div class="alert alert-danger"><strong>Restore failed.</strong>';
      if (data.errors && data.errors.length > 0) {
        html += '<ul class="mb-0 mt-1">';
        data.errors.forEach(function(e) { html += '<li>' + e + '</li>'; });
        html += '</ul>';
      }
      html += '</div>';
      resultDiv.innerHTML = html;
      resultDiv.classList.remove('d-none');
    }
    restoreBtn.disabled = false;
  })
  .catch(function(err) {
    clearInterval(interval);
    progressBar.style.width = '100%';
    progressBar.classList.add('bg-danger');
    statusDiv.textContent = 'An error occurred.';
    resultDiv.innerHTML = '<div class="alert alert-danger">An unexpected error occurred. Please check the server logs.</div>';
    resultDiv.classList.remove('d-none');
    restoreBtn.disabled = false;
  });
}
</script>
@endpush
