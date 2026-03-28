{{-- Shared form fields for create/edit exhibition --}}
<div class="accordion" id="exhibitionForm">
    {{-- Basic Information --}}
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#basic">Basic Information</button></h2>
        <div id="basic" class="accordion-collapse collapse show" data-bs-parent="#exhibitionForm">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title', $exhibition->title ?? '') }}" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="project_code" class="form-label">Project Code</label>
                        <input type="text" name="project_code" id="project_code" class="form-control" value="{{ old('project_code', $exhibition->project_code ?? '') }}">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="subtitle" class="form-label">Subtitle</label>
                    <input type="text" name="subtitle" id="subtitle" class="form-control" value="{{ old('subtitle', $exhibition->subtitle ?? '') }}">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="exhibition_type" class="form-label">Type <span class="text-danger">*</span></label>
                        <select name="exhibition_type" id="exhibition_type" class="form-select @error('exhibition_type') is-invalid @enderror" required>
                            <option value="">Select type...</option>
                            @foreach ($types as $key => $label)
                                <option value="{{ $key }}" {{ old('exhibition_type', $exhibition->exhibition_type ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('exhibition_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach ($statuses as $key => $label)
                                <option value="{{ $key }}" {{ old('status', $exhibition->status ?? 'planning') === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea name="description" id="description" class="form-control" rows="4">{{ old('description', $exhibition->description ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Exhibition Details --}}
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#details">Exhibition Details</button></h2>
        <div id="details" class="accordion-collapse collapse" data-bs-parent="#exhibitionForm">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="theme" class="form-label">Theme</label>
                        <input type="text" name="theme" id="theme" class="form-control" value="{{ old('theme', $exhibition->theme ?? '') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="target_audience" class="form-label">Target Audience</label>
                        <input type="text" name="target_audience" id="target_audience" class="form-control" value="{{ old('target_audience', $exhibition->target_audience ?? '') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ old('start_date', isset($exhibition->start_date) ? \Carbon\Carbon::parse($exhibition->start_date)->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ old('end_date', isset($exhibition->end_date) ? \Carbon\Carbon::parse($exhibition->end_date)->format('Y-m-d') : '') }}">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="venue" class="form-label">Venue</label>
                        <input type="text" name="venue" id="venue" class="form-control" value="{{ old('venue', $exhibition->venue ?? '') }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- People & Budget --}}
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#people">People &amp; Budget</button></h2>
        <div id="people" class="accordion-collapse collapse" data-bs-parent="#exhibitionForm">
            <div class="accordion-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="curator" class="form-label">Curator</label>
                        <input type="text" name="curator" id="curator" class="form-control" value="{{ old('curator', $exhibition->curator ?? '') }}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="designer" class="form-label">Designer</label>
                        <input type="text" name="designer" id="designer" class="form-control" value="{{ old('designer', $exhibition->designer ?? '') }}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="budget" class="form-label">Budget</label>
                        <input type="number" name="budget" id="budget" class="form-control" step="0.01" min="0" value="{{ old('budget', $exhibition->budget ?? '') }}">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="budget_currency" class="form-label">Currency</label>
                        <select name="budget_currency" id="budget_currency" class="form-select">
                            @foreach (['ZAR' => 'ZAR', 'USD' => 'USD', 'EUR' => 'EUR', 'GBP' => 'GBP'] as $code => $label)
                                <option value="{{ $code }}" {{ old('budget_currency', $exhibition->budget_currency ?? 'ZAR') === $code ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
