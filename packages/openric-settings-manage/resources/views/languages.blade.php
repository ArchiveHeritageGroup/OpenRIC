@extends('theme::layouts.1col')

@section('title', 'Language Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-language me-2"></i>I18n Languages</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-globe me-1"></i> Available Languages</h6>
        </div>
        <div class="card-body">
            <p class="text-muted mb-3">
                Select which languages appear in the navigation language dropdown.
                Only languages with a <code>lang/</code> directory on disk are shown.
                <strong>{{ count(array_filter($languages, fn($l) => $l['enabled'])) }}</strong> of <strong>{{ count($languages) }}</strong> enabled.
            </p>

            <form method="POST" action="{{ route('settings.languages') }}">
                @csrf

                <div class="mb-3">
                    <button type="button" class="btn btn-sm btn-outline-primary me-1" id="selectAll"><i class="fas fa-check-double me-1"></i>Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary me-1" id="selectNone"><i class="fas fa-times me-1"></i>Select None</button>
                    <button type="button" class="btn btn-sm btn-outline-info" id="selectSA"><i class="fas fa-flag me-1"></i>SA Official (11)</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px" class="text-center">Show</th>
                                <th style="width:80px">Code</th>
                                <th>Native Name</th>
                                <th>English Name</th>
                                <th style="width:80px">Direction</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($languages as $lang)
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input lang-check"
                                           name="enabled[]" value="{{ $lang['code'] }}"
                                           {{ $lang['enabled'] ? 'checked' : '' }}>
                                </td>
                                <td><code>{{ $lang['code'] }}</code></td>
                                <td>{{ $lang['name'] }}</td>
                                <td class="text-muted">{{ ucfirst(\Locale::getDisplayLanguage($lang['code'], 'en')) }}</td>
                                <td><span class="badge bg-{{ $lang['direction'] === 'rtl' ? 'warning' : 'secondary' }}">{{ strtoupper($lang['direction']) }}</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Language Selection</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('js')
<script>
document.getElementById('selectAll').addEventListener('click', function() {
    document.querySelectorAll('.lang-check').forEach(function(cb) { cb.checked = true; });
});
document.getElementById('selectNone').addEventListener('click', function() {
    document.querySelectorAll('.lang-check').forEach(function(cb) { cb.checked = false; });
});
document.getElementById('selectSA').addEventListener('click', function() {
    var sa = ['af','en','nr','ss','st','tn','ts','ve','xh','zu','nso'];
    document.querySelectorAll('.lang-check').forEach(function(cb) {
        cb.checked = sa.includes(cb.value);
    });
});
</script>
@endpush
@endsection
