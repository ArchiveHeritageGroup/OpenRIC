@extends('theme::layouts.1col')

@section('title', 'Translation Settings')

@section('content')
<div class="d-flex align-items-center mb-3">
  <i class="fas fa-3x fa-language me-3 text-muted" aria-hidden="true"></i>
  <div>
    <h1 class="mb-0">Translation Settings</h1>
    <span class="text-muted">Configure Machine Translation (NLLB-200) endpoint</span>
  </div>
</div>

@if (session('notice'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('notice') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

@if (session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
@endif

<form method="POST" action="{{ route('openric.translation.settings') }}">
  @csrf

  <div class="accordion mb-3">
    {{-- MT Endpoint Configuration --}}
    <div class="accordion-item">
      <h2 class="accordion-header">
        <button class="accordion-button" type="button" data-bs-toggle="collapse"
                data-bs-target="#mt-config-collapse" aria-expanded="true">
          <i class="fas fa-server me-2"></i>MT Endpoint Configuration
        </button>
      </h2>
      <div id="mt-config-collapse" class="accordion-collapse collapse show">
        <div class="accordion-body">
          <div class="mb-3">
            <label class="form-label fw-bold">MT Endpoint URL</label>
            <input class="form-control" name="endpoint" value="{{ $endpoint }}"
                   placeholder="http://192.168.0.112:5004/ai/v1/translate" />
            <small class="form-text text-muted">
              NLLB-200 translation API endpoint. Example: <code>http://192.168.0.112:5004/ai/v1/translate</code>
            </small>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">Timeout (seconds)</label>
            <input class="form-control" name="timeout" value="{{ $timeout }}"
                   type="number" min="5" max="300" step="1" />
            <small class="form-text text-muted">
              Maximum wait time for a translation response. Default: 60 seconds.
            </small>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold">API Key</label>
            <input class="form-control" name="api_key" value="{{ $apiKey }}"
                   type="password" autocomplete="off" />
            <small class="form-text text-muted">
              Optional API key sent as <code>X-API-Key</code> header. Leave blank if not required.
            </small>
          </div>
        </div>
      </div>
    </div>

    {{-- Quick Links --}}
    <div class="accordion-item">
      <h2 class="accordion-header">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                data-bs-target="#quicklinks-collapse" aria-expanded="false">
          <i class="fas fa-link me-2"></i>Quick Links
        </button>
      </h2>
      <div id="quicklinks-collapse" class="accordion-collapse collapse">
        <div class="accordion-body">
          <ul class="list-unstyled mb-0">
            <li class="mb-2">
              <i class="fas fa-heartbeat me-2 text-success"></i>
              <strong>Health Check:</strong>
              <a href="{{ route('openric.translation.health') }}" target="_blank" id="health-link">
                {{ route('openric.translation.health') }}
              </a>
              <button type="button" class="btn btn-sm btn-outline-success ms-2" id="btn-health-check">
                <i class="fas fa-sync-alt me-1"></i>Test Now
              </button>
              <span id="health-result" class="ms-2"></span>
            </li>
            <li class="mb-2">
              <i class="fas fa-globe me-2 text-info"></i>
              <strong>Languages:</strong>
              <a href="{{ route('openric.translation.languages') }}">Manage Languages</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <ul class="actions mb-3 nav gap-2">
    <li><a href="{{ url()->previous() }}" class="btn atom-btn-outline-light" role="button">Cancel</a></li>
    <li><input class="btn atom-btn-outline-success" type="submit" value="Save Settings"></li>
  </ul>
</form>
@endsection

@push('scripts')
<script>
(function(){
  'use strict';

  var btnHealth = document.getElementById('btn-health-check');
  var healthResult = document.getElementById('health-result');

  if (btnHealth) {
    btnHealth.addEventListener('click', function() {
      btnHealth.disabled = true;
      healthResult.innerHTML = '<span class="text-muted">Checking...</span>';

      fetch('{{ route("openric.translation.health") }}')
        .then(function(res) { return res.json(); })
        .then(function(data) {
          if (data.ok) {
            healthResult.innerHTML = '<span class="badge bg-success">OK</span> HTTP ' + data.http_status;
          } else {
            var msg = data.curl_error || ('HTTP ' + data.http_status);
            healthResult.innerHTML = '<span class="badge bg-danger">FAIL</span> ' + msg;
          }
          btnHealth.disabled = false;
        })
        .catch(function(err) {
          healthResult.innerHTML = '<span class="badge bg-danger">ERROR</span> ' + err.message;
          btnHealth.disabled = false;
        });
    });
  }
})();
</script>
@endpush
