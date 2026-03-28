@extends('theme::layouts.1col')

@section('title', 'Language Settings')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3"><i class="fas fa-language me-2"></i>Languages</h1>
        <a href="{{ route('settings.index') }}" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Configured Languages</h6>
            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addLanguageForm"><i class="fas fa-plus me-1"></i> Add Language</button>
        </div>
        <div class="card-body">
            <div class="collapse mb-3" id="addLanguageForm">
                <form method="POST" action="{{ route('settings.languages') }}" class="border rounded p-3 bg-light">
                    @csrf
                    <input type="hidden" name="action" value="add">
                    <div class="row align-items-end">
                        <div class="col-md-3 mb-2">
                            <label for="new_lang_code" class="form-label">Language Code</label>
                            <input type="text" name="code" id="new_lang_code" class="form-control" placeholder="e.g. fr" maxlength="10" required>
                        </div>
                        <div class="col-md-4 mb-2">
                            <label for="new_lang_name" class="form-label">Language Name</label>
                            <input type="text" name="name" id="new_lang_name" class="form-control" placeholder="e.g. French" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="new_lang_direction" class="form-label">Direction</label>
                            <select name="direction" id="new_lang_direction" class="form-select">
                                <option value="ltr">Left to Right</option>
                                <option value="rtl">Right to Left</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus me-1"></i> Add</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Direction</th>
                            <th>Default</th>
                            <th style="width: 80px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($settings['languages'] ?? [] as $lang)
                        <tr>
                            <td><code>{{ $lang['code'] }}</code></td>
                            <td>{{ $lang['name'] }}</td>
                            <td><span class="badge bg-secondary">{{ strtoupper($lang['direction'] ?? 'ltr') }}</span></td>
                            <td>
                                @if ($lang['is_default'] ?? false)
                                    <span class="badge bg-success">Default</span>
                                @endif
                            </td>
                            <td>
                                @unless ($lang['is_default'] ?? false)
                                <form method="POST" action="{{ route('settings.languages') }}" class="d-inline" onsubmit="return confirm('Remove {{ $lang['name'] }}?')">
                                    @csrf
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="code" value="{{ $lang['code'] }}">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                                @endunless
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted">No languages configured.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
