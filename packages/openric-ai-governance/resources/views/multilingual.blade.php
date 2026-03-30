@extends('theme::layouts.1col')
@section('title', 'Multilingual AI Control')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Multilingual AI Control</h1>
    <a href="{{ route('ai-governance.dashboard') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
</div>
@include('theme::partials.alerts')

<p class="text-muted mb-3">Configure which AI capabilities are enabled per language. Toggle switches and save each row independently.</p>

@if(!empty($configs))
<div class="table-responsive">
    <table class="table table-striped align-middle">
        <thead>
            <tr>
                <th>Code</th>
                <th>Language</th>
                <th title="Embedding">Embedding</th>
                <th title="Translation">Translation</th>
                <th title="RAG Retrieval">RAG</th>
                <th title="OCR">OCR</th>
                <th title="Sensitivity Scan">Sensitivity</th>
                <th>Embedding Model</th>
                <th>Translation Model</th>
                <th>Reviewers</th>
                <th>Save</th>
            </tr>
        </thead>
        <tbody>
            @foreach($configs as $cfg)
            @php $lc = $cfg->language_code; @endphp
            <form method="POST" action="{{ route('ai-governance.multilingual.save', $lc) }}">@csrf
            <tr>
                <td><strong>{{ $lc }}</strong></td>
                <td><input type="text" class="form-control form-control-sm" name="language_name" value="{{ $cfg->language_name }}" required style="min-width:100px;"></td>
                <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="embedding_enabled" value="1" @checked($cfg->embedding_enabled)></div></td>
                <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="translation_enabled" value="1" @checked($cfg->translation_enabled)></div></td>
                <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="rag_enabled" value="1" @checked($cfg->rag_enabled)></div></td>
                <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="ocr_enabled" value="1" @checked($cfg->ocr_enabled)></div></td>
                <td><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="sensitivity_scan_enabled" value="1" @checked($cfg->sensitivity_scan_enabled)></div></td>
                <td><input type="text" class="form-control form-control-sm" name="embedding_model" value="{{ $cfg->embedding_model ?? '' }}" placeholder="nomic-embed-text" style="min-width:130px;"></td>
                <td><input type="text" class="form-control form-control-sm" name="translation_model" value="{{ $cfg->translation_model ?? '' }}" placeholder="nllb-200" style="min-width:130px;"></td>
                <td><input type="text" class="form-control form-control-sm" name="reviewer_user_ids" value="{{ is_array($cfg->reviewer_user_ids) ? implode(',', $cfg->reviewer_user_ids) : '' }}" placeholder="User IDs" title="Comma-separated user IDs" style="min-width:80px;"></td>
                <td><button type="submit" class="btn btn-sm btn-primary">Save</button></td>
            </tr>
            </form>
            @endforeach
        </tbody>
    </table>
</div>
@endif

{{-- Add new language --}}
<div class="card mt-4">
    <div class="card-header"><h5 class="mb-0">Add Language Configuration</h5></div>
    <div class="card-body">
        <form method="POST" action="" id="addLangForm" class="row g-2">@csrf
            <div class="col-md-2">
                <label for="new_lang_code" class="form-label">Language Code</label>
                <input type="text" class="form-control form-control-sm" id="new_lang_code" placeholder="e.g. zu" required maxlength="10">
            </div>
            <div class="col-md-3">
                <label for="new_lang_name" class="form-label">Language Name</label>
                <input type="text" class="form-control form-control-sm" id="new_lang_name" name="language_name" required placeholder="e.g. isiZulu">
            </div>
            <div class="col-auto d-flex align-items-end">
                <div class="form-check form-switch me-2"><input class="form-check-input" type="checkbox" name="embedding_enabled" value="1" id="new_emb"><label class="form-check-label small" for="new_emb">Emb</label></div>
                <div class="form-check form-switch me-2"><input class="form-check-input" type="checkbox" name="translation_enabled" value="1" id="new_trl"><label class="form-check-label small" for="new_trl">Trl</label></div>
                <div class="form-check form-switch me-2"><input class="form-check-input" type="checkbox" name="rag_enabled" value="1" id="new_rag"><label class="form-check-label small" for="new_rag">RAG</label></div>
                <div class="form-check form-switch me-2"><input class="form-check-input" type="checkbox" name="ocr_enabled" value="1" id="new_ocr"><label class="form-check-label small" for="new_ocr">OCR</label></div>
                <div class="form-check form-switch me-2"><input class="form-check-input" type="checkbox" name="sensitivity_scan_enabled" value="1" id="new_sen"><label class="form-check-label small" for="new_sen">Sen</label></div>
            </div>
            <div class="col-auto d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-primary" id="addLangBtn">Add Language</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('addLangForm').addEventListener('submit', function(e) {
    e.preventDefault();
    var code = document.getElementById('new_lang_code').value.trim().toLowerCase();
    if (!code) return;
    this.action = '{{ url("admin/ai-governance/multilingual") }}/' + encodeURIComponent(code);
    this.submit();
});
</script>
@endsection
