@extends('theme::layouts.1col')
@section('title', 'Backup & Restore')
@section('body-class', 'admin backup')

@section('content')
<div class="multiline-header d-flex align-items-center mb-3">
  <i class="fas fa-3x fa-database me-3" aria-hidden="true"></i>
  <div class="d-flex flex-column">
    <h1 class="mb-0">Backup & Restore</h1>
    <span class="small text-muted">Manage PostgreSQL database, Fuseki triplestore, and file backups</span>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

{{-- Info Cards --}}
<div class="row g-3 mb-4">

  {{-- Database Info --}}
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-header fw-semibold" style="background:var(--openric-primary, #2c3e50);color:#fff">
        <i class="fas fa-server me-1"></i> PostgreSQL
      </div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-2">
          <tr>
            <td class="text-muted" style="width:80px;">Host</td>
            <td><code>{{ $dbConfig['host'] ?? $dbConfig['unix_socket'] ?? 'localhost' }}</code></td>
          </tr>
          <tr>
            <td class="text-muted">Database</td>
            <td><code>{{ $dbConfig['database'] ?? 'N/A' }}</code></td>
          </tr>
          <tr>
            <td class="text-muted">User</td>
            <td><code>{{ $dbConfig['username'] ?? 'N/A' }}</code></td>
          </tr>
          <tr>
            <td class="text-muted">Status</td>
            <td>
              @if($dbConnected)
                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Connected</span>
                @if($dbVersion)
                  <span class="badge bg-light text-dark ms-1">v{{ $dbVersion }}</span>
                @endif
              @else
                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Disconnected</span>
              @endif
            </td>
          </tr>
        </table>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-test-db" onclick="testDatabaseConnection()">
          <i class="fas fa-plug me-1"></i> Test
        </button>
      </div>
    </div>
  </div>

  {{-- Triplestore Info --}}
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-header fw-semibold" style="background:var(--openric-primary, #2c3e50);color:#fff">
        <i class="fas fa-project-diagram me-1"></i> Fuseki Triplestore
      </div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-2">
          <tr>
            <td class="text-muted" style="width:80px;">URL</td>
            <td><code class="small">{{ config('triplestore.fuseki_url', 'http://localhost:3030') }}</code></td>
          </tr>
          <tr>
            <td class="text-muted">Dataset</td>
            <td><code>{{ $tsDataset }}</code></td>
          </tr>
          <tr>
            <td class="text-muted">Status</td>
            <td>
              @if($tsConnected)
                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Reachable</span>
              @else
                <span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Unreachable</span>
              @endif
            </td>
          </tr>
        </table>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-test-ts" onclick="testTriplestoreConnection()">
          <i class="fas fa-plug me-1"></i> Test
        </button>
      </div>
    </div>
  </div>

  {{-- Storage Info --}}
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-header fw-semibold" style="background:var(--openric-primary, #2c3e50);color:#fff">
        <i class="fas fa-hdd me-1"></i> Storage
      </div>
      <div class="card-body">
        <table class="table table-sm table-borderless mb-0">
          <tr>
            <td class="text-muted" style="width:80px;">Path</td>
            <td><code class="small">{{ $backupPath }}</code></td>
          </tr>
          <tr>
            <td class="text-muted">Backups</td>
            <td><strong>{{ $backupCount }}</strong></td>
          </tr>
          <tr>
            <td class="text-muted">Total Size</td>
            <td><strong>{{ $totalSize }}</strong></td>
          </tr>
          <tr>
            <td class="text-muted">Max Keep</td>
            <td>{{ $maxBackups }}</td>
          </tr>
          <tr>
            <td class="text-muted">Retention</td>
            <td>{{ $retentionDays }} days</td>
          </tr>
          @if($failedCount > 0)
          <tr>
            <td class="text-muted">Failed</td>
            <td><span class="badge bg-danger">{{ $failedCount }}</span></td>
          </tr>
          @endif
        </table>
      </div>
    </div>
  </div>

  {{-- Quick Actions --}}
  <div class="col-md-3">
    <div class="card h-100">
      <div class="card-header fw-semibold" style="background:var(--openric-primary, #2c3e50);color:#fff">
        <i class="fas fa-bolt me-1"></i> Quick Actions
      </div>
      <div class="card-body d-flex flex-column gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickBackup(['database'])">
          <i class="fas fa-database me-1"></i> Database Only
        </button>
        <button type="button" class="btn btn-outline-info btn-sm" onclick="quickBackup(['triplestore'])">
          <i class="fas fa-project-diagram me-1"></i> Triplestore Only
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#createBackupModal">
          <i class="fas fa-archive me-1"></i> Full Backup
        </button>
        <a href="{{ route('backups.restore') }}" class="btn btn-outline-warning btn-sm">
          <i class="fas fa-undo me-1"></i> Restore
        </a>
        <a href="{{ route('backups.upload') }}" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-cloud-upload-alt me-1"></i> Upload
        </a>
        <a href="{{ route('backups.settings') }}" class="btn btn-outline-dark btn-sm">
          <i class="fas fa-cog me-1"></i> Settings
        </a>
      </div>
    </div>
  </div>
</div>

{{-- Backups Table --}}
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center fw-semibold" style="background:var(--openric-primary, #2c3e50);color:#fff">
    <span><i class="fas fa-list me-1"></i> Existing Backups</span>
    <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#createBackupModal">
      <i class="fas fa-plus me-1"></i> Create Backup
    </button>
  </div>
  <div class="card-body p-0">
    @if(count($backups) > 0)
      <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped mb-0">
          <thead>
            <tr>
              <th>Date</th>
              <th>Type</th>
              <th>Components</th>
              <th>Size</th>
              <th>Status</th>
              <th>Created By</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($backups as $backup)
              <tr id="backup-row-{{ $backup['id'] }}">
                <td>
                  <i class="fas fa-clock text-muted me-1"></i>
                  {{ $backup['completed_at'] ?? $backup['created_at'] }}
                </td>
                <td>
                  @php $btype = $backup['type'] ?? 'unknown'; @endphp
                  @if(str_contains($btype, 'full') || str_contains($btype, '+'))
                    <span class="badge bg-success">{{ ucfirst($btype) }}</span>
                  @elseif($btype === 'database')
                    <span class="badge bg-primary">Database</span>
                  @elseif($btype === 'triplestore')
                    <span class="badge bg-info">Triplestore</span>
                  @elseif($btype === 'uploads')
                    <span class="badge bg-warning text-dark">Uploads</span>
                  @elseif($btype === 'packages')
                    <span class="badge bg-secondary">Packages</span>
                  @elseif($btype === 'framework')
                    <span class="badge bg-dark">Framework</span>
                  @else
                    <span class="badge bg-dark">{{ $btype }}</span>
                  @endif
                </td>
                <td>
                  @foreach($backup['components_array'] ?? [] as $comp)
                    @switch($comp)
                      @case('database')
                        <span class="badge bg-primary bg-opacity-25 text-primary" title="PostgreSQL"><i class="fas fa-database"></i> DB</span>
                        @break
                      @case('triplestore')
                        <span class="badge bg-info bg-opacity-25 text-info" title="Fuseki"><i class="fas fa-project-diagram"></i> TS</span>
                        @break
                      @case('uploads')
                        <span class="badge bg-warning bg-opacity-25 text-warning" title="Uploads"><i class="fas fa-upload"></i> Files</span>
                        @break
                      @case('packages')
                        <span class="badge bg-secondary bg-opacity-25 text-secondary" title="Packages"><i class="fas fa-puzzle-piece"></i> Pkg</span>
                        @break
                      @case('framework')
                        <span class="badge bg-dark bg-opacity-25 text-dark" title="Framework"><i class="fas fa-code"></i> FW</span>
                        @break
                    @endswitch
                  @endforeach
                </td>
                <td>{{ $backup['size_human'] }}</td>
                <td>
                  @if($backup['status'] === 'completed')
                    <span class="badge bg-success">Completed</span>
                  @elseif($backup['status'] === 'running')
                    <span class="badge bg-warning text-dark">Running</span>
                  @elseif($backup['status'] === 'failed')
                    <span class="badge bg-danger" title="{{ $backup['error_message'] ?? '' }}">Failed</span>
                  @else
                    <span class="badge bg-secondary">{{ $backup['status'] }}</span>
                  @endif
                </td>
                <td>{{ $backup['created_by_name'] ?? 'System' }}</td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    @if($backup['status'] === 'completed')
                      <a href="{{ route('backups.download', $backup['id']) }}" class="btn btn-outline-secondary" title="Download">
                        <i class="fas fa-download"></i>
                      </a>
                    @endif
                    <button type="button" class="btn btn-outline-danger" title="Delete"
                            onclick="deleteBackup({{ $backup['id'] }}, '{{ addslashes($backup['filename']) }}')">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @else
      <div class="text-center py-5 text-muted">
        <i class="fas fa-3x fa-box-open mb-3 d-block"></i>
        <p class="mb-0">No backups found. Create your first backup using the button above.</p>
      </div>
    @endif
  </div>
</div>

{{-- Create Backup Modal --}}
<div class="modal fade" id="createBackupModal" tabindex="-1" aria-labelledby="createBackupModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createBackupModalLabel"><i class="fas fa-archive me-1"></i> Create Backup</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted mb-3">Select the components to include in the backup:</p>
        <div class="mb-3">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="comp-database" value="database" checked>
            <label class="form-check-label" for="comp-database">
              <i class="fas fa-database text-primary me-1"></i> PostgreSQL Database
            </label>
            <div class="form-text">Full pg_dump of the PostgreSQL database (compressed)</div>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="comp-triplestore" value="triplestore" checked>
            <label class="form-check-label" for="comp-triplestore">
              <i class="fas fa-project-diagram text-info me-1"></i> Fuseki Triplestore
            </label>
            <div class="form-text">N-Quads export of the RiC-O graph data</div>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="comp-uploads" value="uploads">
            <label class="form-check-label" for="comp-uploads">
              <i class="fas fa-upload text-warning me-1"></i> Uploads
              <span class="badge bg-secondary ms-1">Optional</span>
            </label>
            <div class="form-text">Digital object files and uploaded media</div>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="comp-packages" value="packages">
            <label class="form-check-label" for="comp-packages">
              <i class="fas fa-puzzle-piece text-secondary me-1"></i> Packages
              <span class="badge bg-secondary ms-1">Optional</span>
            </label>
            <div class="form-text">All packages in the packages/ directory</div>
          </div>
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="comp-framework" value="framework">
            <label class="form-check-label" for="comp-framework">
              <i class="fas fa-code text-dark me-1"></i> Framework
              <span class="badge bg-secondary ms-1">Optional</span>
            </label>
            <div class="form-text">Application framework files (excludes vendor, node_modules)</div>
          </div>
        </div>
        {{-- Progress --}}
        <div id="backup-progress" class="d-none">
          <div class="progress mb-2">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%" id="backup-progress-bar">0%</div>
          </div>
          <div id="backup-status" class="small text-muted"></div>
        </div>
        {{-- Result --}}
        <div id="backup-result" class="d-none"></div>
      </div>
      <div class="modal-footer" id="backup-modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="btn-start-backup" onclick="startBackup()">
          <i class="fas fa-play me-1"></i> Start Backup
        </button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('js')
<script>
function testDatabaseConnection() {
  var btn = document.getElementById('btn-test-db');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Testing...';

  fetch('{{ route("backups.testDatabase") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
    },
    body: JSON.stringify({}),
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.success) {
      btn.innerHTML = '<i class="fas fa-check text-success me-1"></i> Connected';
    } else {
      btn.innerHTML = '<i class="fas fa-times text-danger me-1"></i> Failed';
    }
    setTimeout(function() {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-plug me-1"></i> Test';
    }, 3000);
  })
  .catch(function() {
    btn.innerHTML = '<i class="fas fa-times text-danger me-1"></i> Error';
    setTimeout(function() {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-plug me-1"></i> Test';
    }, 3000);
  });
}

function testTriplestoreConnection() {
  var btn = document.getElementById('btn-test-ts');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Testing...';

  fetch('{{ route("backups.testTriplestore") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
    },
    body: JSON.stringify({}),
  })
  .then(function(r) { return r.json(); })
  .then(function(data) {
    if (data.success) {
      btn.innerHTML = '<i class="fas fa-check text-success me-1"></i> Reachable';
    } else {
      btn.innerHTML = '<i class="fas fa-times text-danger me-1"></i> Failed';
    }
    setTimeout(function() {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-plug me-1"></i> Test';
    }, 3000);
  })
  .catch(function() {
    btn.innerHTML = '<i class="fas fa-times text-danger me-1"></i> Error';
    setTimeout(function() {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-plug me-1"></i> Test';
    }, 3000);
  });
}

function quickBackup(components) {
  runBackup(components);
}

function startBackup() {
  var components = [];
  if (document.getElementById('comp-database').checked) components.push('database');
  if (document.getElementById('comp-triplestore').checked) components.push('triplestore');
  if (document.getElementById('comp-uploads').checked) components.push('uploads');
  if (document.getElementById('comp-packages').checked) components.push('packages');
  if (document.getElementById('comp-framework').checked) components.push('framework');

  if (components.length === 0) {
    alert('Please select at least one component.');
    return;
  }

  runBackup(components);
}

function runBackup(components) {
  var progressDiv = document.getElementById('backup-progress');
  var progressBar = document.getElementById('backup-progress-bar');
  var statusDiv = document.getElementById('backup-status');
  var resultDiv = document.getElementById('backup-result');
  var startBtn = document.getElementById('btn-start-backup');

  if (progressDiv) progressDiv.classList.remove('d-none');
  if (resultDiv) {
    resultDiv.classList.add('d-none');
    resultDiv.innerHTML = '';
  }
  if (startBtn) startBtn.disabled = true;

  // Simulate progress
  var progress = 0;
  var interval = setInterval(function() {
    progress += Math.random() * 15;
    if (progress > 90) progress = 90;
    if (progressBar) {
      progressBar.style.width = Math.round(progress) + '%';
      progressBar.textContent = Math.round(progress) + '%';
    }
  }, 500);

  if (statusDiv) statusDiv.textContent = 'Creating backup for: ' + components.join(', ') + '...';

  fetch('{{ route("backups.create") }}', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
    },
    body: JSON.stringify({ components: components }),
  })
  .then(function(response) { return response.json(); })
  .then(function(data) {
    clearInterval(interval);
    if (progressBar) {
      progressBar.style.width = '100%';
      progressBar.textContent = '100%';
      progressBar.classList.remove('progress-bar-animated');
    }

    if (data.success) {
      if (progressBar) progressBar.classList.add('bg-success');
      if (statusDiv) statusDiv.textContent = data.message;

      var html = '<div class="alert alert-success mt-2 mb-0"><strong>Backup created successfully!</strong><ul class="mb-0 mt-1">';
      if (data.files) {
        data.files.forEach(function(f) {
          html += '<li>' + f.component + ': ' + f.filename + ' (' + f.size + ')</li>';
        });
      }
      html += '</ul>';
      if (data.errors && data.errors.length > 0) {
        html += '<hr><strong>Warnings:</strong><ul class="mb-0">';
        data.errors.forEach(function(e) { html += '<li class="text-warning">' + e + '</li>'; });
        html += '</ul>';
      }
      html += '</div>';
      if (resultDiv) {
        resultDiv.innerHTML = html;
        resultDiv.classList.remove('d-none');
      }
      setTimeout(function() { location.reload(); }, 3000);
    } else {
      if (progressBar) progressBar.classList.add('bg-danger');
      if (statusDiv) statusDiv.textContent = 'Backup failed.';

      var html = '<div class="alert alert-danger mt-2 mb-0"><strong>Backup failed.</strong>';
      if (data.errors && data.errors.length > 0) {
        html += '<ul class="mb-0 mt-1">';
        data.errors.forEach(function(e) { html += '<li>' + e + '</li>'; });
        html += '</ul>';
      }
      html += '</div>';
      if (resultDiv) {
        resultDiv.innerHTML = html;
        resultDiv.classList.remove('d-none');
      }
    }
    if (startBtn) startBtn.disabled = false;
  })
  .catch(function(err) {
    clearInterval(interval);
    if (progressBar) {
      progressBar.style.width = '100%';
      progressBar.classList.add('bg-danger');
    }
    if (statusDiv) statusDiv.textContent = 'An error occurred.';
    if (resultDiv) {
      resultDiv.innerHTML = '<div class="alert alert-danger mt-2 mb-0">An unexpected error occurred. Please check the server logs.</div>';
      resultDiv.classList.remove('d-none');
    }
    if (startBtn) startBtn.disabled = false;
  });
}

function deleteBackup(id, filename) {
  if (!confirm('Are you sure you want to delete backup "' + filename + '"? This cannot be undone.')) {
    return;
  }

  fetch('/admin/backups/' + id, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
    },
  })
  .then(function(response) { return response.json(); })
  .then(function(data) {
    if (data.success) {
      var row = document.getElementById('backup-row-' + id);
      if (row) row.remove();
    } else {
      alert(data.message || 'Failed to delete backup.');
    }
  })
  .catch(function() {
    alert('An error occurred while deleting the backup.');
  });
}
</script>
@endpush
