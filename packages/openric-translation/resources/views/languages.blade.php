@extends('theme::layouts.1col')

@section('title', 'Translation Languages')

@section('content')
<div class="d-flex align-items-center mb-3">
  <i class="fas fa-3x fa-globe me-3 text-muted" aria-hidden="true"></i>
  <div>
    <h1 class="mb-0">Translation Languages</h1>
    <span class="text-muted">
      {{ count(array_filter($languages, fn($l) => $l['enabled'])) }} of {{ count($languages) }} languages enabled
    </span>
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

<div class="accordion mb-3">
  {{-- Enabled Languages --}}
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button" type="button" data-bs-toggle="collapse"
              data-bs-target="#languages-collapse" aria-expanded="true">
        <i class="fas fa-list me-2"></i>All Languages
      </button>
    </h2>
    <div id="languages-collapse" class="accordion-collapse collapse show">
      <div class="accordion-body">
        <table class="table table-striped table-sm table-hover">
          <thead>
            <tr>
              <th style="width:80px;">Code</th>
              <th>Name</th>
              <th style="width:120px;">Status</th>
              <th style="width:100px;">Group</th>
            </tr>
          </thead>
          <tbody>
            @php
              $saLanguages = ['en','af','zu','xh','st','tn','ss','ts','ve','nr','nso'];
            @endphp
            @foreach ($languages as $lang)
              <tr>
                <td><code>{{ $lang['code'] }}</code></td>
                <td>{{ $lang['name'] }}</td>
                <td>
                  @if ($lang['default'])
                    <span class="badge bg-primary">Default</span>
                  @elseif ($lang['enabled'])
                    <span class="badge bg-success">Enabled</span>
                  @else
                    <span class="badge bg-secondary">Available</span>
                  @endif
                </td>
                <td>
                  @if (in_array($lang['code'], $saLanguages))
                    <span class="badge bg-warning text-dark">SA Official</span>
                  @else
                    <span class="badge bg-info">International</span>
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="text-muted small mt-2">
          <strong>SA Official (11):</strong> English, Afrikaans, isiZulu, isiXhosa, Sesotho, Setswana, SiSwati, Xitsonga, Tshivenda, isiNdebele, Sepedi.
          <br>
          <strong>International (8):</strong> French, German, Portuguese, Spanish, Dutch, Italian, Arabic, Chinese.
        </div>
      </div>
    </div>
  </div>

  {{-- Add Language --}}
  <div class="accordion-item">
    <h2 class="accordion-header">
      <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
              data-bs-target="#add-language-collapse" aria-expanded="false">
        <i class="fas fa-plus me-2"></i>Enable Language
      </button>
    </h2>
    <div id="add-language-collapse" class="accordion-collapse collapse">
      <div class="accordion-body">
        @php
          $availableToAdd = array_filter($languages, fn($l) => !$l['enabled']);
        @endphp

        @if (count($availableToAdd) > 0)
          <form method="POST" action="{{ route('openric.translation.addLanguage') }}">
            @csrf
            <div class="row align-items-end">
              <div class="col-md-4">
                <label class="form-label fw-bold">Language code</label>
                <select class="form-select" name="code">
                  @foreach ($languages as $lang)
                    @if (!$lang['enabled'])
                      <option value="{{ $lang['code'] }}">{{ $lang['name'] }} ({{ $lang['code'] }})</option>
                    @endif
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <button type="submit" class="btn atom-btn-outline-success">
                  <i class="fas fa-plus me-1"></i>Enable Language
                </button>
              </div>
            </div>
          </form>
        @else
          <div class="alert alert-info mb-0">
            <i class="fas fa-check-circle me-1"></i>
            All {{ count($languages) }} supported languages are already enabled.
          </div>
        @endif
      </div>
    </div>
  </div>
</div>

<ul class="actions mb-3 nav gap-2">
  <li>
    <a href="{{ route('openric.translation.settings') }}" class="btn atom-btn-outline-light" role="button">
      <i class="fas fa-arrow-left me-1"></i>Back to Settings
    </a>
  </li>
</ul>
@endsection
