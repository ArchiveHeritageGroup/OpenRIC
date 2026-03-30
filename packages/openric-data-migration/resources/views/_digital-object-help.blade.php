{{-- Digital Object Import Help Partial --}}
<div class="card mb-3">
  <div class="card-header fw-semibold" style="background:var(--bs-primary, #0d6efd);color:#fff">
    <i class="fas fa-question-circle me-2"></i>Digital Object Import
  </div>
  <div class="card-body">
    <p>To import digital objects alongside descriptions:</p>
    <ul>
      <li>Include a <code>digitalObjectURI</code> column with the file URL or path</li>
      <li>Include a <code>digitalObjectPath</code> column for local file paths</li>
      <li>Supported formats: JPEG, PNG, TIFF, PDF, MP3, MP4</li>
      <li>Maximum file size depends on server configuration</li>
    </ul>
    <p class="mb-0"><strong>Note:</strong> Digital objects will be downloaded/copied to the uploads directory during import.</p>
  </div>
</div>
