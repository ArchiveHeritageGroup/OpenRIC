@extends('theme::layouts.1col')
@section('title', 'Backup Settings')
@section('body-class', 'admin backup settings')

@section('content')
<div class="multiline-header d-flex align-items-center mb-3">
  <i class="fas fa-3x fa-cog me-3" aria-hidden="true"></i>
  <div class="d-flex flex-column">
    <h1 class="mb-0">Backup Settings</h1>
    <span class="small text-muted">Configure backup storage, retention, and scheduling</span>
  </div>
</div>

<div class="mb-3">
  <a href="{{ route('backups.index') }}" class="btn btn-sm btn-outline-secondary">
    <i class="fas fa-arrow-left me-1"></i> Back to Backups
  </a>
</div>

@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if($errors->any())
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card mb-4">
  <div class="card-header fw-semibold" style="background:var(--openric-primary, #2c3e50);color:#fff">
    <i class="fas fa-sliders-h me-1"></i> Storage Settings
  </div>
  <div class="card-body">
    <form method="post" action="{{ route('backups.saveSettings') }}">
      @csrf

      <div class="mb-3">
        <label for="backup_path" class="form-label">Backup Path</label>
        <input type="text" class="form-control @error('backup_path') is-invalid @enderror" id="backup_path" name="backup_path"
               value="{{ old('backup_path', $settings['backup_path']) }}">
        <div class="form-text">Absolute path on the server where backups will be stored.</div>
        @error('backup_path')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <div class="row">
        <div class="col-md-6">
          <div class="mb-3">
            <label for="backup_max_backups" class="form-label">Max Backups</label>
            <input type="number" class="form-control @error('backup_max_backups') is-invalid @enderror" id="backup_max_backups" name="backup_max_backups"
                   value="{{ old('backup_max_backups', $settings['backup_max_backups']) }}" min="1" max="999">
            <div class="form-text">Maximum number of backups to keep. Older backups are deleted automatically.</div>
            @error('backup_max_backups')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="backup_retention_days" class="form-label">Retention Days</label>
            <input type="number" class="form-control @error('backup_retention_days') is-invalid @enderror" id="backup_retention_days" name="backup_retention_days"
                   value="{{ old('backup_retention_days', $settings['backup_retention_days']) }}" min="1" max="3650">
            <div class="form-text">Backups older than this number of days are deleted automatically.</div>
            @error('backup_retention_days')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label for="backup_notification_email" class="form-label">Notification Email <span class="badge bg-secondary ms-1">Optional</span></label>
        <input type="email" class="form-control @error('backup_notification_email') is-invalid @enderror" id="backup_notification_email" name="backup_notification_email"
               value="{{ old('backup_notification_email', $settings['backup_notification_email']) }}" placeholder="admin@example.com">
        <div class="form-text">Email address to receive backup completion notifications. Leave blank to disable.</div>
        @error('backup_notification_email')
          <div class="invalid-feedback">{{ $message }}</div>
        @enderror
      </div>

      <hr>

      <h6><i class="fas fa-calendar-alt me-1"></i> Scheduled Backups</h6>

      <div class="row">
        <div class="col-md-6">
          <div class="mb-3">
            <label for="backup_enabled" class="form-label">Enable Scheduled Backups</label>
            <select class="form-select @error('backup_enabled') is-invalid @enderror" id="backup_enabled" name="backup_enabled">
              <option value="0" @if(!old('backup_enabled', $settings['backup_enabled'])) selected @endif>Disabled</option>
              <option value="1" @if(old('backup_enabled', $settings['backup_enabled'])) selected @endif>Enabled</option>
            </select>
            @error('backup_enabled')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
        <div class="col-md-6">
          <div class="mb-3">
            <label for="backup_frequency" class="form-label">Frequency</label>
            <select class="form-select @error('backup_frequency') is-invalid @enderror" id="backup_frequency" name="backup_frequency">
              <option value="daily" @if(old('backup_frequency', $settings['backup_frequency']) === 'daily') selected @endif>Daily</option>
              <option value="weekly" @if(old('backup_frequency', $settings['backup_frequency']) === 'weekly') selected @endif>Weekly</option>
              <option value="monthly" @if(old('backup_frequency', $settings['backup_frequency']) === 'monthly') selected @endif>Monthly</option>
            </select>
            @error('backup_frequency')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>
        </div>
      </div>

      <div class="mt-3 p-3 rounded" style="background:#495057;">
        <button type="submit" class="btn btn-success">
          <i class="fas fa-save me-1"></i> Save Settings
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
