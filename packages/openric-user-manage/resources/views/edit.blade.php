@extends('theme::layouts.1col')

@section('title')
  <div class="multiline-header d-flex flex-column mb-3">
    <h1 class="mb-0">{{ $user ? 'Edit' : 'Add new' }} user</h1>
    @if($user)
      <span class="small">{{ is_array($user) ? ($user['authorized_form_of_name'] ?? $user['username'] ?? '') : ($user->authorized_form_of_name ?? $user->username ?? '') }}</span>
    @endif
  </div>
@endsection

@php
  $username = is_array($user ?? null) ? ($user['username'] ?? '') : ($user->username ?? '');
  $email_ = is_array($user ?? null) ? ($user['email'] ?? '') : ($user->email ?? '');
  $active_ = is_array($user ?? null) ? ($user['active'] ?? 1) : ($user->active ?? 1);
  $slug_ = is_array($user ?? null) ? ($user['slug'] ?? '') : ($user->slug ?? '');
  $afon_ = is_array($user ?? null) ? ($user['authorized_form_of_name'] ?? '') : ($user->authorized_form_of_name ?? '');
  $contact_ = is_array($user ?? null) ? ($user['contact'] ?? null) : ($user->contact ?? null);
  if (is_array($contact_)) $contact_ = (object) $contact_;
  $currentGroupIds = [];
  if ($user) {
      $groups_ = is_array($user) ? ($user['groups'] ?? []) : ($user->groups ?? []);
      foreach ($groups_ as $g) {
          $currentGroupIds[] = (int)(is_array($g) ? ($g['id'] ?? 0) : ($g->id ?? 0));
      }
  }
  $currentTranslate = is_array($user ?? null) ? ($user['translateLanguages'] ?? []) : ($user->translateLanguages ?? []);
@endphp

@section('content')
  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
  @endif

  <form method="POST" action="{{ $user ? route('user.update', $slug_) : route('user.store') }}" autocomplete="off">
    @csrf

    <div class="accordion mb-3">

      {{-- Basic info --}}
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basicInfo-collapse" aria-expanded="true">Basic info</button></h2>
        <div id="basicInfo-collapse" class="accordion-collapse collapse show">
          <div class="accordion-body">
            <div class="mb-3">
              <label for="username" class="form-label">Username <span class="text-danger">*</span> <span class="badge bg-danger ms-1">Required</span></label>
              <input type="text" name="username" id="username" class="form-control" required autocomplete="off" value="{{ old('username', $username) }}">
            </div>
            <div class="mb-3">
              <label for="email" class="form-label">Email <span class="text-danger">*</span> <span class="badge bg-danger ms-1">Required</span></label>
              <input type="email" name="email" id="email" class="form-control" required autocomplete="off" value="{{ old('email', $email_) }}">
            </div>
            @if($user)
              <div class="mb-3">
                <label for="current_password" class="form-label">Current password <span class="badge bg-warning ms-1">Required to change password</span></label>
                <input type="password" name="current_password" id="current_password" class="form-control" autocomplete="current-password">
                <div class="form-text">Enter your current password to confirm identity before changing the password.</div>
              </div>
            @endif
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="password" class="form-label">Password @if(!$user)<span class="text-danger">*</span>@endif <span class="badge bg-danger ms-1">Required</span></label>
                <input type="password" name="password" id="password" class="form-control" {{ $user ? '' : 'required' }} autocomplete="new-password">
                <div class="progress mt-1" style="height: 5px;"><div class="progress-bar" role="progressbar" style="width: 0%;" id="passwordStrengthFill"></div></div>
                <div class="form-text" id="passwordStrengthText">@if($user) Leave blank to keep current password. @endif</div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="confirm_password" class="form-label">Confirm password <span class="badge bg-secondary ms-1">Optional</span></label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" autocomplete="new-password">
                <div class="form-text" id="passwordMatchText"></div>
              </div>
            </div>
            <div class="mb-3">
              <label for="active" class="form-label">Active <span class="badge bg-secondary ms-1">Optional</span></label>
              <select name="active" id="active" class="form-select">
                <option value="1" {{ old('active', $active_) == 1 ? 'selected' : '' }}>Active</option>
                <option value="0" {{ old('active', $active_) == 0 ? 'selected' : '' }}>Inactive</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {{-- Profile --}}
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#profile-collapse" aria-expanded="true">Profile</button></h2>
        <div id="profile-collapse" class="accordion-collapse collapse show">
          <div class="accordion-body">
            <div class="mb-3">
              <label for="authorized_form_of_name" class="form-label">User Name <span class="badge bg-warning ms-1">Recommended</span></label>
              <input type="text" name="authorized_form_of_name" id="authorized_form_of_name" class="form-control" value="{{ old('authorized_form_of_name', $afon_) }}">
              <div class="form-text">Display name for this user.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Contact information --}}
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#contactInfo-collapse" aria-expanded="true">Contact information</button></h2>
        <div id="contactInfo-collapse" class="accordion-collapse collapse show">
          <div class="accordion-body">
            <div class="row">
              <div class="col-md-6 mb-3"><label for="contact_telephone" class="form-label">Telephone <span class="badge bg-secondary ms-1">Optional</span></label><input type="text" name="contact_telephone" id="contact_telephone" class="form-control" value="{{ old('contact_telephone', $contact_->telephone ?? '') }}"></div>
              <div class="col-md-6 mb-3"><label for="contact_fax" class="form-label">Fax <span class="badge bg-secondary ms-1">Optional</span></label><input type="text" name="contact_fax" id="contact_fax" class="form-control" value="{{ old('contact_fax', $contact_->fax ?? '') }}"></div>
            </div>
            <div class="mb-3"><label for="contact_street_address" class="form-label">Street address <span class="badge bg-secondary ms-1">Optional</span></label><input type="text" name="contact_street_address" id="contact_street_address" class="form-control" value="{{ old('contact_street_address', $contact_->street_address ?? '') }}"></div>
            <div class="row">
              <div class="col-md-4 mb-3"><label for="contact_city" class="form-label">City <span class="badge bg-secondary ms-1">Optional</span></label><input type="text" name="contact_city" id="contact_city" class="form-control" value="{{ old('contact_city', $contact_->city ?? '') }}"></div>
              <div class="col-md-4 mb-3"><label for="contact_region" class="form-label">Region/province <span class="badge bg-secondary ms-1">Optional</span></label><input type="text" name="contact_region" id="contact_region" class="form-control" value="{{ old('contact_region', $contact_->region ?? '') }}"></div>
              <div class="col-md-4 mb-3"><label for="contact_postal_code" class="form-label">Postal code <span class="badge bg-secondary ms-1">Optional</span></label><input type="text" name="contact_postal_code" id="contact_postal_code" class="form-control" value="{{ old('contact_postal_code', $contact_->postal_code ?? '') }}"></div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3"><label for="contact_country_code" class="form-label">Country <span class="badge bg-secondary ms-1">Optional</span></label><input type="text" name="contact_country_code" id="contact_country_code" class="form-control" value="{{ old('contact_country_code', $contact_->country_code ?? '') }}"></div>
              <div class="col-md-6 mb-3"><label for="contact_website" class="form-label">Website <span class="badge bg-secondary ms-1">Optional</span></label><input type="url" name="contact_website" id="contact_website" class="form-control" value="{{ old('contact_website', $contact_->website ?? '') }}"></div>
            </div>
            <div class="mb-3"><label for="contact_note" class="form-label">Note <span class="badge bg-secondary ms-1">Optional</span></label><textarea name="contact_note" id="contact_note" class="form-control" rows="2">{{ old('contact_note', $contact_->contact_note ?? '') }}</textarea></div>
          </div>
        </div>
      </div>

      {{-- Access control --}}
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#accessControl-collapse" aria-expanded="true">Access control</button></h2>
        <div id="accessControl-collapse" class="accordion-collapse collapse show">
          <div class="accordion-body">
            <div class="mb-3">
              <label for="groups" class="form-label">User groups <span class="badge bg-secondary ms-1">Optional</span></label>
              <select name="groups[]" id="groups" class="form-select" multiple size="{{ min(max(count($assignableGroups), 3), 8) }}">
                @foreach($assignableGroups as $group)
                  @php $gid = is_array($group) ? ($group['id'] ?? 0) : ($group->id ?? 0); $gname = is_array($group) ? ($group['name'] ?? '') : ($group->name ?? ''); @endphp
                  <option value="{{ $gid }}" {{ in_array((int) $gid, old('groups', $currentGroupIds)) ? 'selected' : '' }}>{{ $gname }}</option>
                @endforeach
              </select>
              <div class="form-text">Hold Ctrl/Cmd to select multiple groups.</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Allowed languages for translation --}}
      <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#translate-collapse" aria-expanded="true">Allowed languages for translation</button></h2>
        <div id="translate-collapse" class="accordion-collapse collapse show">
          <div class="accordion-body">
            @if(!empty($availableLanguages))
              <div class="mb-3">
                <label for="translate" class="form-label">Translate <span class="badge bg-secondary ms-1">Optional</span></label>
                <select name="translate[]" id="translate" class="form-select" multiple size="{{ min(max(count($availableLanguages), 3), 8) }}">
                  @foreach($availableLanguages as $lang)
                    <option value="{{ $lang }}" {{ in_array($lang, old('translate', $currentTranslate)) ? 'selected' : '' }}>{{ locale_get_display_language($lang, app()->getLocale()) ?: $lang }}</option>
                  @endforeach
                </select>
                <div class="form-text">Hold Ctrl/Cmd to select multiple languages.</div>
              </div>
            @else
              <p class="text-muted mb-0">No languages configured.</p>
            @endif
          </div>
        </div>
      </div>

    </div>

    <ul class="nav gap-2 mb-3">
      @if($user)
        <li><a href="{{ route('user.show', $slug_) }}" class="btn btn-outline-secondary">Cancel</a></li>
        <li><input class="btn btn-outline-success" type="submit" value="Save"></li>
      @else
        <li><a href="{{ route('user.browse') }}" class="btn btn-outline-secondary">Cancel</a></li>
        <li><input class="btn btn-outline-success" type="submit" value="Create"></li>
      @endif
    </ul>
  </form>

  <script>
  (function() {
    var pw = document.getElementById('password');
    var cpw = document.getElementById('confirm_password');
    var strengthFill = document.getElementById('passwordStrengthFill');
    var strengthText = document.getElementById('passwordStrengthText');
    var matchText = document.getElementById('passwordMatchText');

    function checkStrength(val) {
      if (val.length === 0) { strengthFill.style.width = '0%'; strengthFill.className = 'progress-bar'; return; }
      var score = 0;
      if (val.length >= 8) score++; if (val.length >= 12) score++;
      if (/[a-z]/.test(val) && /[A-Z]/.test(val)) score++;
      if (/\d/.test(val)) score++; if (/[^a-zA-Z0-9]/.test(val)) score++;
      var pct, cls;
      if (score <= 1) { pct = '20%'; cls = 'progress-bar bg-danger'; }
      else if (score === 2) { pct = '40%'; cls = 'progress-bar bg-warning'; }
      else if (score === 3) { pct = '60%'; cls = 'progress-bar bg-info'; }
      else if (score === 4) { pct = '80%'; cls = 'progress-bar bg-primary'; }
      else { pct = '100%'; cls = 'progress-bar bg-success'; }
      strengthFill.style.width = pct; strengthFill.className = cls;
    }

    function checkMatch() {
      if (cpw.value.length === 0) { matchText.textContent = ''; return; }
      matchText.textContent = pw.value === cpw.value ? 'Passwords match.' : 'Passwords do not match.';
      matchText.className = 'form-text ' + (pw.value === cpw.value ? 'text-success' : 'text-danger');
    }

    if (pw) { pw.addEventListener('input', function() { checkStrength(this.value); checkMatch(); }); }
    if (cpw) { cpw.addEventListener('input', checkMatch); }
  })();
  </script>
@endsection
