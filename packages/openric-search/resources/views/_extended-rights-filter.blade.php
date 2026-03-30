{{-- Extended Rights Search Filter --}}
<fieldset class="mb-3">
  <legend class="h6">Rights</legend>
  <div class="row">
    <div class="col-md-4 mb-2">
      <label for="rights_statement_filter" class="form-label small">Rights Statement</label>
      <select name="rights_statement_id" id="rights_statement_filter" class="form-select form-select-sm">
        <option value="">Any</option>
        <option value="none">No rights statement</option>
      </select>
    </div>
    <div class="col-md-4 mb-2">
      <label for="cc_license_filter" class="form-label small">CC License</label>
      <select name="cc_license_id" id="cc_license_filter" class="form-select form-select-sm">
        <option value="">Any</option>
        <option value="none">No CC license</option>
      </select>
    </div>
    <div class="col-md-4 mb-2">
      <label for="embargo_status_filter" class="form-label small">Embargo Status</label>
      <select name="embargo_status" id="embargo_status_filter" class="form-select form-select-sm">
        <option value="">Any</option>
        <option value="active">Under embargo</option>
        <option value="none">Not embargoed</option>
        <option value="expiring">Expiring within 30 days</option>
      </select>
    </div>
  </div>
</fieldset>
