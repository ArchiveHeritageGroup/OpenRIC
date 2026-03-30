{{-- Enhanced Search Toggle --}}
<div class="enhanced-search-toggle d-inline-flex align-items-center ms-2" title="Smart search uses synonyms and related terms">
  <div class="form-check form-switch mb-0">
    <input class="form-check-input" type="checkbox" role="switch" id="enhancedSearchToggle">
    <label class="form-check-label small text-muted" for="enhancedSearchToggle">
      <i class="fas fa-brain me-1"></i>Smart
    </label>
  </div>
</div>

<script>
document.getElementById('enhancedSearchToggle')?.addEventListener('change', function() {
    sessionStorage.setItem('enhancedSearch', this.checked ? '1' : '0');
});
</script>
