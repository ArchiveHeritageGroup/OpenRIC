@extends('theme::layouts.1col')

@section('title', 'Create Record Part — ISAD(G)')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Create Record Part <span class="badge bg-secondary">ISAD(G)</span></h1>
        @include('theme::partials.view-switch')
    </div>

    @include('theme::partials.alerts')

    <form method="POST" action="{{ route('record-parts.store') }}" id="isadgForm">
        @csrf
        <input type="hidden" name="_form_type" value="isadg">

        <div class="accordion" id="isadgAccordion">

            {{-- ===== 3.1 Identity Statement Area ===== --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="identity-heading">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse"
                            data-bs-target="#identity-collapse" aria-expanded="true" aria-controls="identity-collapse">
                        3.1 Identity Statement Area
                    </button>
                </h2>
                <div id="identity-collapse" class="accordion-collapse collapse show" aria-labelledby="identity-heading">
                    <div class="accordion-body">

                        {{-- 3.1.1 Reference code --}}
                        <div class="mb-3">
                            <label for="identifier" class="form-label">3.1.1 Reference code(s) <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="identifier" name="identifier"
                                       value="{{ old('identifier') }}">
                                <button type="button" class="btn btn-outline-secondary" id="generate-identifier">
                                    <i class="fas fa-cog me-1" aria-hidden="true"></i>Generate
                                </button>
                            </div>
                            <div class="form-text text-muted small">Provide a specific local reference code, control number, or other unique identifier. The country and repository code will be automatically added from the linked repository record to form a full reference code. (ISAD 3.1.1)</div>
                        </div>

                        {{-- 3.1.2 Title --}}
                        <div class="mb-3">
                            <label for="title" class="form-label">3.1.2 Title <span class="text-danger">*</span> <span class="badge bg-danger ms-1">Required</span></label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                                   value="{{ old('title') }}" required>
                            @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text text-muted small">Provide either a formal title or a concise supplied title in accordance with the rules of multilevel description and national conventions. (ISAD 3.1.2)</div>
                        </div>

                        {{-- Alternate title --}}
                        <div class="mb-3">
                            <label for="alternate_title" class="form-label">Alternate title <span class="badge bg-secondary ms-1">Optional</span></label>
                            <input type="text" class="form-control" id="alternate_title" name="alternate_title"
                                   value="{{ old('alternate_title') }}">
                            <div class="form-text text-muted small">Use this field to record any alternative or parallel title(s) for the unit of description.</div>
                        </div>

                        {{-- 3.1.3 Date(s) — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">3.1.3 Date(s) <span class="badge bg-secondary ms-1">Optional</span></label>
                            <table class="table table-sm" id="events-table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Date display</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th style="width:80px"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>
                                            <select class="form-select form-select-sm" name="events[0][type]">
                                                <option value="">- Select -</option>
                                                <option value="creation" {{ old('events.0.type') === 'creation' ? 'selected' : '' }}>Creation</option>
                                                <option value="accumulation" {{ old('events.0.type') === 'accumulation' ? 'selected' : '' }}>Accumulation</option>
                                                <option value="contribution" {{ old('events.0.type') === 'contribution' ? 'selected' : '' }}>Contribution</option>
                                                <option value="publication" {{ old('events.0.type') === 'publication' ? 'selected' : '' }}>Publication</option>
                                                <option value="collection" {{ old('events.0.type') === 'collection' ? 'selected' : '' }}>Collection</option>
                                                <option value="broadcast" {{ old('events.0.type') === 'broadcast' ? 'selected' : '' }}>Broadcast</option>
                                                <option value="manufacturing" {{ old('events.0.type') === 'manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                                                <option value="distribution" {{ old('events.0.type') === 'distribution' ? 'selected' : '' }}>Distribution</option>
                                            </select>
                                        </td>
                                        <td><input type="text" class="form-control form-control-sm" name="events[0][date]" value="{{ old('events.0.date') }}" placeholder="e.g. ca. 1900"></td>
                                        <td><input type="date" class="form-control form-control-sm" name="events[0][startDate]" value="{{ old('events.0.startDate') }}"></td>
                                        <td><input type="date" class="form-control form-control-sm" name="events[0][endDate]" value="{{ old('events.0.endDate') }}"></td>
                                        <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">Remove</button></td>
                                    </tr>
                                </tbody>
                            </table>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-event-row">Add date</button>
                            <div class="form-text text-muted small">Identify and record the date(s) of the unit of description. Identify the type of date given. Record as a single date or a range of dates as appropriate. (ISAD 3.1.3). The Date display field can be used to enter free-text date information, including typographical marks to express approximation, uncertainty, or qualification. Use the start and end fields to make the dates searchable.</div>
                        </div>

                        {{-- 3.1.4 Level of description --}}
                        <div class="mb-3">
                            <label for="level" class="form-label">3.1.4 Level of description <span class="text-danger">*</span> <span class="badge bg-danger ms-1">Required</span></label>
                            <select class="form-select @error('level') is-invalid @enderror" id="level" name="level" required>
                                <option value="">- Select -</option>
                                @foreach(['fonds', 'subfonds', 'collection', 'series', 'subseries', 'file', 'subfile', 'item', 'part'] as $lvl)
                                    <option value="{{ $lvl }}" {{ old('level') === $lvl ? 'selected' : '' }}>{{ ucfirst($lvl) }}</option>
                                @endforeach
                            </select>
                            @error('level') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text text-muted small">Record the level of this unit of description. (ISAD 3.1.4)</div>
                        </div>

                        {{-- 3.1.5 Extent and medium --}}
                        <div class="mb-3">
                            <label for="extent_and_medium" class="form-label">3.1.5 Extent and medium <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="extent_and_medium" name="extent_and_medium" rows="3">{{ old('extent_and_medium') }}</textarea>
                            <div class="form-text text-muted small">Record the extent of the unit of description by giving the number of physical or logical units in arabic numerals and the unit of measurement. Give the specific medium (media) of the unit of description. Separate multiple extents with a linebreak. (ISAD 3.1.5)</div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ===== 3.2 Context Area ===== --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="context-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#context-collapse" aria-expanded="false" aria-controls="context-collapse">
                        3.2 Context Area
                    </button>
                </h2>
                <div id="context-collapse" class="accordion-collapse collapse" aria-labelledby="context-heading">
                    <div class="accordion-body">

                        {{-- 3.2.1 Name of creator(s) — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">3.2.1 Name of creator(s) <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div id="creators-list">
                                <div class="input-group input-group-sm mb-1">
                                    <input type="text" class="form-control" name="creators[0]" value="{{ old('creators.0') }}" placeholder="Creator name">
                                    <button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-creator-row">Add creator</button>
                            <div class="form-text text-muted small">Record the name of the organization(s) or the individual(s) responsible for the creation, accumulation and maintenance of the records in the unit of description. (ISAD 3.2.1)</div>
                        </div>

                        {{-- Repository --}}
                        <div class="mb-3">
                            <label for="repository" class="form-label">Repository <span class="badge bg-secondary ms-1">Optional</span></label>
                            <input type="text" class="form-control" id="repository" name="repository"
                                   value="{{ old('repository') }}" placeholder="Repository name">
                            <div class="form-text text-muted small">Record the name of the organization which has custody of the archival material.</div>
                        </div>

                        {{-- 3.2.2 Administrative/biographical history --}}
                        <div class="mb-3">
                            <label for="admin_history" class="form-label">3.2.2 Administrative/biographical history <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="admin_history" name="admin_history" rows="3">{{ old('admin_history') }}</textarea>
                            <div class="form-text text-muted small">Record in narrative form or as a chronology the main life events, activities, achievements and/or roles of the entity being described. This may include information on gender, nationality, family and religious or political affiliations. (ISAD 3.2.2)</div>
                        </div>

                        {{-- 3.2.3 Archival history --}}
                        <div class="mb-3">
                            <label for="archival_history" class="form-label">3.2.3 Archival history <span class="badge bg-warning ms-1">Recommended</span></label>
                            <textarea class="form-control" id="archival_history" name="archival_history" rows="3">{{ old('archival_history') }}</textarea>
                            <div class="form-text text-muted small">Record the successive transfers of ownership, responsibility and/or custody of the unit of description and indicate those actions, such as history of the arrangement, production of contemporary finding aids, re-use of the records for other purposes or software migrations, that have contributed to its present structure and arrangement. (ISAD 3.2.3)</div>
                        </div>

                        {{-- 3.2.4 Immediate source of acquisition --}}
                        <div class="mb-3">
                            <label for="acquisition" class="form-label">3.2.4 Immediate source of acquisition or transfer <span class="badge bg-warning ms-1">Recommended</span></label>
                            <textarea class="form-control" id="acquisition" name="acquisition" rows="3">{{ old('acquisition') }}</textarea>
                            <div class="form-text text-muted small">Record the source from which the unit of description was acquired and the date and/or method of acquisition if any or all of this information is not confidential. If the source is unknown, record that information. Optionally, add accession numbers or codes. (ISAD 3.2.4)</div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ===== 3.3 Content and Structure Area ===== --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="content-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#content-collapse" aria-expanded="false" aria-controls="content-collapse">
                        3.3 Content and Structure Area
                    </button>
                </h2>
                <div id="content-collapse" class="accordion-collapse collapse" aria-labelledby="content-heading">
                    <div class="accordion-body">

                        {{-- 3.3.1 Scope and content --}}
                        <div class="mb-3">
                            <label for="scope_and_content" class="form-label">3.3.1 Scope and content <span class="badge bg-warning ms-1">Recommended</span></label>
                            <textarea class="form-control" id="scope_and_content" name="scope_and_content" rows="4">{{ old('scope_and_content') }}</textarea>
                            <div class="form-text text-muted small">Give a summary of the scope (such as, time periods, geography) and content, (such as documentary forms, subject matter, administrative processes) of the unit of description, appropriate to the level of description. (ISAD 3.3.1)</div>
                        </div>

                        {{-- 3.3.2 Appraisal, destruction and scheduling --}}
                        <div class="mb-3">
                            <label for="appraisal" class="form-label">3.3.2 Appraisal, destruction and scheduling <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="appraisal" name="appraisal" rows="3">{{ old('appraisal') }}</textarea>
                            <div class="form-text text-muted small">Record appraisal, destruction and scheduling actions taken on or planned for the unit of description, especially if they may affect the interpretation of the material. (ISAD 3.3.2)</div>
                        </div>

                        {{-- 3.3.3 Accruals --}}
                        <div class="mb-3">
                            <label for="accruals" class="form-label">3.3.3 Accruals <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="accruals" name="accruals" rows="3">{{ old('accruals') }}</textarea>
                            <div class="form-text text-muted small">Indicate if accruals are expected. Where appropriate, give an estimate of their quantity and frequency. (ISAD 3.3.3)</div>
                        </div>

                        {{-- 3.3.4 System of arrangement --}}
                        <div class="mb-3">
                            <label for="arrangement" class="form-label">3.3.4 System of arrangement <span class="badge bg-warning ms-1">Recommended</span></label>
                            <textarea class="form-control" id="arrangement" name="arrangement" rows="3">{{ old('arrangement') }}</textarea>
                            <div class="form-text text-muted small">Specify the internal structure, order and/or the system of classification of the unit of description. Note how these have been treated by the archivist. For electronic records, record or reference information on system design. (ISAD 3.3.4)</div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ===== 3.4 Conditions of Access and Use Area ===== --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="conditions-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#conditions-collapse" aria-expanded="false" aria-controls="conditions-collapse">
                        3.4 Conditions of Access and Use Area
                    </button>
                </h2>
                <div id="conditions-collapse" class="accordion-collapse collapse" aria-labelledby="conditions-heading">
                    <div class="accordion-body">

                        {{-- 3.4.1 Conditions governing access --}}
                        <div class="mb-3">
                            <label for="access_conditions" class="form-label">3.4.1 Conditions governing access <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="access_conditions" name="access_conditions" rows="3">{{ old('access_conditions') }}</textarea>
                            <div class="form-text text-muted small">Specify the law or legal status, contract, regulation or policy that affects access to the unit of description. Indicate the extent of the period of closure and the date at which the material will open when appropriate. (ISAD 3.4.1)</div>
                        </div>

                        {{-- 3.4.2 Conditions governing reproduction --}}
                        <div class="mb-3">
                            <label for="reproduction_conditions" class="form-label">3.4.2 Conditions governing reproduction <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="reproduction_conditions" name="reproduction_conditions" rows="3">{{ old('reproduction_conditions') }}</textarea>
                            <div class="form-text text-muted small">Give information about conditions, such as copyright, governing the reproduction of the unit of description after access has been provided. If the existence of such conditions is unknown, record this. If there are no conditions, no statement is necessary. (ISAD 3.4.2)</div>
                        </div>

                        {{-- 3.4.3 Language(s) of material — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">3.4.3 Language(s) of material <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div id="languages-list">
                                <div class="input-group input-group-sm mb-1">
                                    <select class="form-select form-select-sm" name="languages[]">
                                        <option value="">-- Select language --</option>
                                        <option value="en" {{ old('languages.0') === 'en' ? 'selected' : '' }}>English (en)</option>
                                        <option value="af" {{ old('languages.0') === 'af' ? 'selected' : '' }}>Afrikaans (af)</option>
                                        <option value="nl" {{ old('languages.0') === 'nl' ? 'selected' : '' }}>Dutch (nl)</option>
                                        <option value="de" {{ old('languages.0') === 'de' ? 'selected' : '' }}>German (de)</option>
                                        <option value="fr" {{ old('languages.0') === 'fr' ? 'selected' : '' }}>French (fr)</option>
                                        <option value="zu" {{ old('languages.0') === 'zu' ? 'selected' : '' }}>Zulu (zu)</option>
                                        <option value="xh" {{ old('languages.0') === 'xh' ? 'selected' : '' }}>Xhosa (xh)</option>
                                        <option value="st" {{ old('languages.0') === 'st' ? 'selected' : '' }}>Sesotho (st)</option>
                                        <option value="tn" {{ old('languages.0') === 'tn' ? 'selected' : '' }}>Setswana (tn)</option>
                                        <option value="nso" {{ old('languages.0') === 'nso' ? 'selected' : '' }}>Sepedi (nso)</option>
                                        <option value="ts" {{ old('languages.0') === 'ts' ? 'selected' : '' }}>Tsonga (ts)</option>
                                        <option value="ss" {{ old('languages.0') === 'ss' ? 'selected' : '' }}>Swati (ss)</option>
                                        <option value="ve" {{ old('languages.0') === 've' ? 'selected' : '' }}>Venda (ve)</option>
                                        <option value="nr" {{ old('languages.0') === 'nr' ? 'selected' : '' }}>Ndebele (nr)</option>
                                        <option value="pt" {{ old('languages.0') === 'pt' ? 'selected' : '' }}>Portuguese (pt)</option>
                                        <option value="es" {{ old('languages.0') === 'es' ? 'selected' : '' }}>Spanish (es)</option>
                                        <option value="it" {{ old('languages.0') === 'it' ? 'selected' : '' }}>Italian (it)</option>
                                        <option value="la" {{ old('languages.0') === 'la' ? 'selected' : '' }}>Latin (la)</option>
                                        <option value="grc" {{ old('languages.0') === 'grc' ? 'selected' : '' }}>Ancient Greek (grc)</option>
                                        <option value="he" {{ old('languages.0') === 'he' ? 'selected' : '' }}>Hebrew (he)</option>
                                        <option value="ar" {{ old('languages.0') === 'ar' ? 'selected' : '' }}>Arabic (ar)</option>
                                        <option value="fa" {{ old('languages.0') === 'fa' ? 'selected' : '' }}>Persian (fa)</option>
                                        <option value="hi" {{ old('languages.0') === 'hi' ? 'selected' : '' }}>Hindi (hi)</option>
                                        <option value="zh" {{ old('languages.0') === 'zh' ? 'selected' : '' }}>Chinese (zh)</option>
                                        <option value="ja" {{ old('languages.0') === 'ja' ? 'selected' : '' }}>Japanese (ja)</option>
                                        <option value="ko" {{ old('languages.0') === 'ko' ? 'selected' : '' }}>Korean (ko)</option>
                                        <option value="ru" {{ old('languages.0') === 'ru' ? 'selected' : '' }}>Russian (ru)</option>
                                        <option value="sw" {{ old('languages.0') === 'sw' ? 'selected' : '' }}>Swahili (sw)</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-add-lang-row" data-target="languages-list" data-name="languages[]">Add language</button>
                            <div class="form-text text-muted small">Record the language(s) of the materials comprising the unit of description. (ISAD 3.4.3)</div>
                        </div>

                        {{-- 3.4.3 Script(s) of material — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">3.4.3 Script(s) of material <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div id="scripts-list">
                                <div class="input-group input-group-sm mb-1">
                                    <select class="form-select form-select-sm" name="scripts[]">
                                        <option value="">-- Select script --</option>
                                        <option value="Latn" {{ old('scripts.0') === 'Latn' ? 'selected' : '' }}>Latin (Latn)</option>
                                        <option value="Cyrl" {{ old('scripts.0') === 'Cyrl' ? 'selected' : '' }}>Cyrillic (Cyrl)</option>
                                        <option value="Arab" {{ old('scripts.0') === 'Arab' ? 'selected' : '' }}>Arabic (Arab)</option>
                                        <option value="Grek" {{ old('scripts.0') === 'Grek' ? 'selected' : '' }}>Greek (Grek)</option>
                                        <option value="Hebr" {{ old('scripts.0') === 'Hebr' ? 'selected' : '' }}>Hebrew (Hebr)</option>
                                        <option value="Deva" {{ old('scripts.0') === 'Deva' ? 'selected' : '' }}>Devanagari (Deva)</option>
                                        <option value="Hans" {{ old('scripts.0') === 'Hans' ? 'selected' : '' }}>Chinese Simplified (Hans)</option>
                                        <option value="Hant" {{ old('scripts.0') === 'Hant' ? 'selected' : '' }}>Chinese Traditional (Hant)</option>
                                        <option value="Jpan" {{ old('scripts.0') === 'Jpan' ? 'selected' : '' }}>Japanese (Jpan)</option>
                                        <option value="Kore" {{ old('scripts.0') === 'Kore' ? 'selected' : '' }}>Korean (Kore)</option>
                                        <option value="Thai" {{ old('scripts.0') === 'Thai' ? 'selected' : '' }}>Thai (Thai)</option>
                                        <option value="Geor" {{ old('scripts.0') === 'Geor' ? 'selected' : '' }}>Georgian (Geor)</option>
                                        <option value="Armn" {{ old('scripts.0') === 'Armn' ? 'selected' : '' }}>Armenian (Armn)</option>
                                        <option value="Ethi" {{ old('scripts.0') === 'Ethi' ? 'selected' : '' }}>Ethiopic (Ethi)</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-add-script-row" data-target="scripts-list" data-name="scripts[]">Add script</button>
                            <div class="form-text text-muted small">Record the script(s) of the materials comprising the unit of description. (ISAD 3.4.3)</div>
                        </div>

                        {{-- Language and script notes --}}
                        <div class="mb-3">
                            <label for="language_notes" class="form-label">3.4.3 Language and script notes <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="language_notes" name="language_notes" rows="2">{{ old('language_notes') }}</textarea>
                            <div class="form-text text-muted small">Note any distinctive alphabets, scripts, symbol systems or abbreviations employed. (ISAD 3.4.3)</div>
                        </div>

                        {{-- 3.4.4 Physical characteristics and technical requirements --}}
                        <div class="mb-3">
                            <label for="physical_characteristics" class="form-label">3.4.4 Physical characteristics and technical requirements <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="physical_characteristics" name="physical_characteristics" rows="3">{{ old('physical_characteristics') }}</textarea>
                            <div class="form-text text-muted small">Indicate any important physical conditions, such as preservation requirements, that affect the use of the unit of description. Note any software and/or hardware required to access the unit of description. (ISAD 3.4.4)</div>
                        </div>

                        {{-- 3.4.5 Finding aids --}}
                        <div class="mb-3">
                            <label for="finding_aids" class="form-label">3.4.5 Finding aids <span class="badge bg-warning ms-1">Recommended</span></label>
                            <textarea class="form-control" id="finding_aids" name="finding_aids" rows="3">{{ old('finding_aids') }}</textarea>
                            <div class="form-text text-muted small">Give information about any finding aids that the repository or records creator may have that provide information relating to the context and contents of the unit of description. If appropriate, include information on where to obtain a copy. (ISAD 3.4.5)</div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ===== 3.5 Allied Materials Area ===== --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="allied-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#allied-collapse" aria-expanded="false" aria-controls="allied-collapse">
                        3.5 Allied Materials Area
                    </button>
                </h2>
                <div id="allied-collapse" class="accordion-collapse collapse" aria-labelledby="allied-heading">
                    <div class="accordion-body">

                        {{-- 3.5.1 Existence and location of originals --}}
                        <div class="mb-3">
                            <label for="location_of_originals" class="form-label">3.5.1 Existence and location of originals <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="location_of_originals" name="location_of_originals" rows="3">{{ old('location_of_originals') }}</textarea>
                            <div class="form-text text-muted small">If the original of the unit of description is available (either in the institution or elsewhere) record its location, together with any significant control numbers. If the originals no longer exist, or their location is unknown, give that information. (ISAD 3.5.1)</div>
                        </div>

                        {{-- 3.5.2 Existence and location of copies --}}
                        <div class="mb-3">
                            <label for="location_of_copies" class="form-label">3.5.2 Existence and location of copies <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="location_of_copies" name="location_of_copies" rows="3">{{ old('location_of_copies') }}</textarea>
                            <div class="form-text text-muted small">If the copy of the unit of description is available (either in the institution or elsewhere) record its location, together with any significant control numbers. (ISAD 3.5.2)</div>
                        </div>

                        {{-- 3.5.3 Related units of description --}}
                        <div class="mb-3">
                            <label for="related_units_of_description" class="form-label">3.5.3 Related units of description <span class="badge bg-warning ms-1">Recommended</span></label>
                            <textarea class="form-control" id="related_units_of_description" name="related_units_of_description" rows="3">{{ old('related_units_of_description') }}</textarea>
                            <div class="form-text text-muted small">Record information about units of description in the same repository or elsewhere that are related by provenance or other association(s). Use appropriate introductory wording and explain the nature of the relationship. If the related unit of description is a finding aid, use the finding aids element of description (3.4.5) to make the reference to it. (ISAD 3.5.3)</div>
                        </div>

                        {{-- 3.5.4 Publication notes — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">3.5.4 Publication notes <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div id="pubnotes-list">
                                <div class="mb-1">
                                    <div class="input-group input-group-sm">
                                        <textarea class="form-control form-control-sm" name="publicationNotes[0][content]" rows="2">{{ old('publicationNotes.0.content') }}</textarea>
                                        <button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-pubnote-row">Add publication note</button>
                            <div class="form-text text-muted small">Record a citation to, and/or information about a publication that is about or based on the use, study, or analysis of the unit of description. Include references to published facsimiles or transcriptions. (ISAD 3.5.4)</div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ===== 3.6 Notes Area ===== --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="notes-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#notes-collapse" aria-expanded="false" aria-controls="notes-collapse">
                        3.6 Notes Area
                    </button>
                </h2>
                <div id="notes-collapse" class="accordion-collapse collapse" aria-labelledby="notes-heading">
                    <div class="accordion-body">

                        {{-- 3.6.1 Notes — repeatable type + content --}}
                        <table class="table table-sm" id="notes-table">
                            <thead>
                                <tr>
                                    <th style="width:30%">Type</th>
                                    <th>Content</th>
                                    <th style="width:80px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select class="form-select form-select-sm" name="notes[0][type]">
                                            <option value="">- Select -</option>
                                            <option value="general" {{ old('notes.0.type') === 'general' ? 'selected' : '' }}>General note</option>
                                            <option value="conservation" {{ old('notes.0.type') === 'conservation' ? 'selected' : '' }}>Conservation</option>
                                            <option value="physical_description" {{ old('notes.0.type') === 'physical_description' ? 'selected' : '' }}>Physical description</option>
                                            <option value="accompanying_material" {{ old('notes.0.type') === 'accompanying_material' ? 'selected' : '' }}>Accompanying material</option>
                                            <option value="alpha_numeric_designation" {{ old('notes.0.type') === 'alpha_numeric_designation' ? 'selected' : '' }}>Alpha-numeric designation</option>
                                            <option value="rights" {{ old('notes.0.type') === 'rights' ? 'selected' : '' }}>Rights</option>
                                            <option value="language" {{ old('notes.0.type') === 'language' ? 'selected' : '' }}>Language</option>
                                            <option value="publication" {{ old('notes.0.type') === 'publication' ? 'selected' : '' }}>Publication</option>
                                        </select>
                                    </td>
                                    <td><textarea class="form-control form-control-sm" name="notes[0][content]" rows="2">{{ old('notes.0.content') }}</textarea></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">Remove</button></td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="add-note-row">Add note</button>
                        <div class="form-text text-muted small">Record any other significant information not covered by other areas. (ISAD 3.6.1)</div>

                    </div>
                </div>
            </div>

            {{-- ===== 3.7 Description Control Area ===== --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="description-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#description-collapse" aria-expanded="false" aria-controls="description-collapse">
                        3.7 Description Control Area
                    </button>
                </h2>
                <div id="description-collapse" class="accordion-collapse collapse" aria-labelledby="description-heading">
                    <div class="accordion-body">

                        {{-- 3.7.1 Description identifier --}}
                        <div class="mb-3">
                            <label for="description_identifier" class="form-label">3.7.1 Description identifier <span class="badge bg-warning ms-1">Recommended</span></label>
                            <input type="text" class="form-control" id="description_identifier" name="description_identifier"
                                   value="{{ old('description_identifier') }}">
                            <div class="form-text text-muted small">Record a unique description identifier in accordance with local and/or national conventions. If the description is to be used internationally, record the code of the country in which the description was created in accordance with the latest version of ISO 3166. (ISAD 3.7.1)</div>
                        </div>

                        {{-- Institution responsible --}}
                        <div class="mb-3">
                            <label for="institution_responsible_identifier" class="form-label">3.7.1 Institution identifier <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="institution_responsible_identifier" name="institution_responsible_identifier" rows="2">{{ old('institution_responsible_identifier') }}</textarea>
                            <div class="form-text text-muted small">Record the full authorised form of name(s) of the agency(ies) responsible for creating, modifying or disseminating the description or, alternatively, record a code for the agency in accordance with the national or international agency code standard.</div>
                        </div>

                        {{-- 3.7.2 Rules or conventions --}}
                        <div class="mb-3">
                            <label for="rules" class="form-label">3.7.2 Rules or conventions <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="rules" name="rules" rows="3">{{ old('rules') }}</textarea>
                            <div class="form-text text-muted small">Record the international, national and/or local rules or conventions followed in preparing the description. (ISAD 3.7.2)</div>
                        </div>

                        {{-- 3.7.3 Status --}}
                        <div class="mb-3">
                            <label for="description_status" class="form-label">3.7.3 Status <span class="badge bg-secondary ms-1">Optional</span></label>
                            <select class="form-select" id="description_status" name="description_status">
                                <option value="">-- Select --</option>
                                <option value="draft" {{ old('description_status') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="final" {{ old('description_status') === 'final' ? 'selected' : '' }}>Final</option>
                                <option value="revised" {{ old('description_status') === 'revised' ? 'selected' : '' }}>Revised</option>
                            </select>
                            <div class="form-text text-muted small">Record the current status of the description, indicating whether it is a draft, finalized and/or revised or deleted. (ISAD 3.7.3)</div>
                        </div>

                        {{-- 3.7.4 Level of detail --}}
                        <div class="mb-3">
                            <label for="description_detail" class="form-label">3.7.4 Level of detail <span class="badge bg-secondary ms-1">Optional</span></label>
                            <select class="form-select" id="description_detail" name="description_detail">
                                <option value="">-- Select --</option>
                                <option value="full" {{ old('description_detail') === 'full' ? 'selected' : '' }}>Full</option>
                                <option value="partial" {{ old('description_detail') === 'partial' ? 'selected' : '' }}>Partial</option>
                                <option value="minimal" {{ old('description_detail') === 'minimal' ? 'selected' : '' }}>Minimal</option>
                            </select>
                            <div class="form-text text-muted small">Record whether the description consists of a minimal, partial or full level of detail in accordance with relevant international and/or national guidelines and/or rules. (ISAD 3.7.4)</div>
                        </div>

                        {{-- 3.7.5 Dates of creation, revision and deletion --}}
                        <div class="mb-3">
                            <label for="revision_history" class="form-label">3.7.5 Dates of creation, revision and deletion <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea class="form-control" id="revision_history" name="revision_history" rows="3">{{ old('revision_history') }}</textarea>
                            <div class="form-text text-muted small">Record the date(s) the entry was prepared and/or revised. (ISAD 3.7.5)</div>
                        </div>

                        {{-- Language(s) of description — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">Language(s) of description <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div id="langs-of-desc-list">
                                <div class="input-group input-group-sm mb-1">
                                    <select class="form-select form-select-sm" name="languagesOfDescription[]">
                                        <option value="">-- Select language --</option>
                                        <option value="en">English (en)</option>
                                        <option value="af">Afrikaans (af)</option>
                                        <option value="nl">Dutch (nl)</option>
                                        <option value="de">German (de)</option>
                                        <option value="fr">French (fr)</option>
                                        <option value="zu">Zulu (zu)</option>
                                        <option value="xh">Xhosa (xh)</option>
                                        <option value="st">Sesotho (st)</option>
                                        <option value="tn">Setswana (tn)</option>
                                        <option value="nso">Sepedi (nso)</option>
                                        <option value="ts">Tsonga (ts)</option>
                                        <option value="ss">Swati (ss)</option>
                                        <option value="ve">Venda (ve)</option>
                                        <option value="nr">Ndebele (nr)</option>
                                        <option value="pt">Portuguese (pt)</option>
                                        <option value="es">Spanish (es)</option>
                                        <option value="it">Italian (it)</option>
                                        <option value="la">Latin (la)</option>
                                        <option value="grc">Ancient Greek (grc)</option>
                                        <option value="he">Hebrew (he)</option>
                                        <option value="ar">Arabic (ar)</option>
                                        <option value="fa">Persian (fa)</option>
                                        <option value="hi">Hindi (hi)</option>
                                        <option value="zh">Chinese (zh)</option>
                                        <option value="ja">Japanese (ja)</option>
                                        <option value="ko">Korean (ko)</option>
                                        <option value="ru">Russian (ru)</option>
                                        <option value="sw">Swahili (sw)</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-add-lang-row" data-target="langs-of-desc-list" data-name="languagesOfDescription[]">Add language</button>
                            <div class="form-text text-muted small">Indicate the language(s) used to create the description of the archival material.</div>
                        </div>

                        {{-- Script(s) of description — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">Script(s) of description <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div id="scripts-of-desc-list">
                                <div class="input-group input-group-sm mb-1">
                                    <select class="form-select form-select-sm" name="scriptsOfDescription[]">
                                        <option value="">-- Select script --</option>
                                        <option value="Latn">Latin (Latn)</option>
                                        <option value="Cyrl">Cyrillic (Cyrl)</option>
                                        <option value="Arab">Arabic (Arab)</option>
                                        <option value="Grek">Greek (Grek)</option>
                                        <option value="Hebr">Hebrew (Hebr)</option>
                                        <option value="Deva">Devanagari (Deva)</option>
                                        <option value="Hans">Chinese Simplified (Hans)</option>
                                        <option value="Hant">Chinese Traditional (Hant)</option>
                                        <option value="Jpan">Japanese (Jpan)</option>
                                        <option value="Kore">Korean (Kore)</option>
                                        <option value="Thai">Thai (Thai)</option>
                                        <option value="Geor">Georgian (Geor)</option>
                                        <option value="Armn">Armenian (Armn)</option>
                                        <option value="Ethi">Ethiopic (Ethi)</option>
                                    </select>
                                    <button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-add-script-row" data-target="scripts-of-desc-list" data-name="scriptsOfDescription[]">Add script</button>
                            <div class="form-text text-muted small">Indicate the script(s) used to create the description of the archival material.</div>
                        </div>

                        {{-- Sources --}}
                        <div class="mb-3">
                            <label for="sources" class="form-label">Sources <span class="badge bg-warning ms-1">Recommended</span></label>
                            <textarea class="form-control" id="sources" name="sources" rows="3">{{ old('sources') }}</textarea>
                            <div class="form-text text-muted small">Record citations for any external sources used in the archival description (such as the Scope and Content, Archival History, or Notes fields).</div>
                        </div>

                        {{-- Source standard --}}
                        <div class="mb-3">
                            <label for="source_standard" class="form-label">Source standard <span class="badge bg-secondary ms-1">Optional</span></label>
                            <input type="text" class="form-control" id="source_standard" name="source_standard"
                                   value="{{ old('source_standard') }}">
                            <div class="form-text text-muted small">Record the standard used when entering the description of the archival material (e.g. ISAD(G), RAD, DACS).</div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ===== Access Points ===== --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="access-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#access-collapse" aria-expanded="false" aria-controls="access-collapse">
                        Access Points
                    </button>
                </h2>
                <div id="access-collapse" class="accordion-collapse collapse" aria-labelledby="access-heading">
                    <div class="accordion-body">

                        {{-- Subject access points — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">Subject access points <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div id="subjects-list">
                                <div class="input-group input-group-sm mb-1">
                                    <input type="text" class="form-control" name="subjects[0]" value="{{ old('subjects.0') }}" placeholder="Type to add subject...">
                                    <button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-subject-row">Add subject</button>
                            <div class="form-text text-muted small">Record subject terms that describe the content of the unit of description.</div>
                        </div>

                        {{-- Place access points — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">Place access points <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div id="places-list">
                                <div class="input-group input-group-sm mb-1">
                                    <input type="text" class="form-control" name="places[0]" value="{{ old('places.0') }}" placeholder="Type to add place...">
                                    <button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-place-row">Add place</button>
                            <div class="form-text text-muted small">Record place names that are relevant to the unit of description.</div>
                        </div>

                        {{-- Name access points — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">Name access points (subjects) <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div id="names-list">
                                <div class="input-group input-group-sm mb-1">
                                    <input type="text" class="form-control" name="nameAccessPoints[0]" value="{{ old('nameAccessPoints.0') }}" placeholder="Type to add name...">
                                    <button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-name-row">Add name</button>
                            <div class="form-text text-muted small">Record the names of persons, families, or organizations that are relevant as subjects of the unit of description.</div>
                        </div>

                        {{-- Genre access points — repeatable --}}
                        <div class="mb-3">
                            <label class="form-label">Genre access points <span class="badge bg-secondary ms-1">Optional</span></label>
                            <div id="genres-list">
                                <div class="input-group input-group-sm mb-1">
                                    <input type="text" class="form-control" name="genres[0]" value="{{ old('genres.0') }}" placeholder="Type to add genre...">
                                    <button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-genre-row">Add genre</button>
                            <div class="form-text text-muted small">Record the genre(s) or form(s) of material present in the unit of description.</div>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ===== Security Classification ===== --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="security-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#security-collapse" aria-expanded="false" aria-controls="security-collapse">
                        Security Classification
                    </button>
                </h2>
                <div id="security-collapse" class="accordion-collapse collapse" aria-labelledby="security-heading">
                    <div class="accordion-body">

                        {{-- Classification level --}}
                        <div class="mb-3">
                            <label for="security_classification" class="form-label">Classification level <span class="badge bg-secondary ms-1">Optional</span></label>
                            <select name="security_classification" id="security_classification" class="form-select">
                                <option value="">-- None --</option>
                                <option value="unclassified" {{ old('security_classification') === 'unclassified' ? 'selected' : '' }}>Unclassified</option>
                                <option value="restricted" {{ old('security_classification') === 'restricted' ? 'selected' : '' }}>Restricted</option>
                                <option value="confidential" {{ old('security_classification') === 'confidential' ? 'selected' : '' }}>Confidential</option>
                                <option value="secret" {{ old('security_classification') === 'secret' ? 'selected' : '' }}>Secret</option>
                                <option value="top_secret" {{ old('security_classification') === 'top_secret' ? 'selected' : '' }}>Top Secret</option>
                            </select>
                        </div>

                        {{-- Reason --}}
                        <div class="mb-3">
                            <label for="security_reason" class="form-label">Reason <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea name="security_reason" id="security_reason" class="form-control" rows="2">{{ old('security_reason') }}</textarea>
                        </div>

                        {{-- Review date / Declassify date --}}
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="security_review_date" class="form-label">Review date <span class="badge bg-secondary ms-1">Optional</span></label>
                                <input type="date" name="security_review_date" id="security_review_date" class="form-control"
                                       value="{{ old('security_review_date') }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="security_declassify_date" class="form-label">Declassify date <span class="badge bg-secondary ms-1">Optional</span></label>
                                <input type="date" name="security_declassify_date" id="security_declassify_date" class="form-control"
                                       value="{{ old('security_declassify_date') }}">
                            </div>
                        </div>

                        {{-- Handling instructions --}}
                        <div class="mb-3">
                            <label for="security_handling_instructions" class="form-label">Handling instructions <span class="badge bg-secondary ms-1">Optional</span></label>
                            <textarea name="security_handling_instructions" id="security_handling_instructions" class="form-control" rows="2">{{ old('security_handling_instructions') }}</textarea>
                        </div>

                        {{-- Inherit to children --}}
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" name="security_inherit_to_children" id="security_inherit_to_children" value="1" {{ old('security_inherit_to_children') ? 'checked' : '' }}>
                            <label class="form-check-label" for="security_inherit_to_children">Apply classification to children</label>
                        </div>

                    </div>
                </div>
            </div>

            {{-- ===== Administration ===== --}}
            <div class="accordion-item">
                <h2 class="accordion-header" id="admin-heading">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#admin-collapse" aria-expanded="false" aria-controls="admin-collapse">
                        Administration
                    </button>
                </h2>
                <div id="admin-collapse" class="accordion-collapse collapse" aria-labelledby="admin-heading">
                    <div class="accordion-body">

                        {{-- Publication status --}}
                        <div class="mb-3">
                            <label for="publication_status" class="form-label">Publication status <span class="badge bg-secondary ms-1">Optional</span></label>
                            <select name="publication_status" id="publication_status" class="form-select">
                                <option value="draft" {{ old('publication_status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ old('publication_status') === 'published' ? 'selected' : '' }}>Published</option>
                            </select>
                        </div>

                        {{-- Display standard --}}
                        <div class="mb-3">
                            <label for="display_standard" class="form-label">Display standard <span class="badge bg-secondary ms-1">Optional</span></label>
                            <select name="display_standard" id="display_standard" class="form-select">
                                <option value="">- Use global default -</option>
                                <option value="isadg" {{ old('display_standard') === 'isadg' ? 'selected' : '' }}>ISAD(G)</option>
                                <option value="rad" {{ old('display_standard') === 'rad' ? 'selected' : '' }}>RAD</option>
                                <option value="dacs" {{ old('display_standard') === 'dacs' ? 'selected' : '' }}>DACS</option>
                                <option value="dc" {{ old('display_standard') === 'dc' ? 'selected' : '' }}>Dublin Core</option>
                                <option value="mods" {{ old('display_standard') === 'mods' ? 'selected' : '' }}>MODS</option>
                            </select>
                        </div>

                        {{-- Parent --}}
                        <div class="mb-3">
                            <label for="parent_iri" class="form-label">Parent Record IRI <span class="badge bg-secondary ms-1">Optional</span></label>
                            <input type="text" class="form-control" id="parent_iri" name="parent_iri"
                                   value="{{ old('parent_iri') }}" placeholder="IRI of parent record">
                            <div class="form-text text-muted small">Enter the IRI of the parent record if this description is part of a hierarchical arrangement.</div>
                        </div>

                    </div>
                </div>
            </div>

        </div>{{-- end accordion --}}

        {{-- ===== Form actions ===== --}}
        <div class="mt-3 mb-4">
            <button type="submit" class="btn btn-primary">Create Record Part</button>
            <a href="{{ route('record-parts.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    /* ---- Generic remove handlers ---- */
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-row')) {
            e.target.closest('tr').remove();
        }
        if (e.target.classList.contains('btn-remove-multi')) {
            e.target.closest('.input-group, .mb-1').remove();
        }
    });

    /* ---- Generate identifier ---- */
    var genBtn = document.getElementById('generate-identifier');
    if (genBtn) {
        genBtn.addEventListener('click', function() {
            var ts = Date.now().toString(36).toUpperCase();
            document.getElementById('identifier').value = 'ORIC-' + ts;
        });
    }

    /* ---- Add event (date) row ---- */
    var addEventBtn = document.getElementById('add-event-row');
    if (addEventBtn) {
        addEventBtn.addEventListener('click', function() {
            var tbody = document.querySelector('#events-table tbody');
            var idx = tbody.querySelectorAll('tr').length;
            var typeOptions = '<option value="">- Select -</option>'
                + '<option value="creation">Creation</option>'
                + '<option value="accumulation">Accumulation</option>'
                + '<option value="contribution">Contribution</option>'
                + '<option value="publication">Publication</option>'
                + '<option value="collection">Collection</option>'
                + '<option value="broadcast">Broadcast</option>'
                + '<option value="manufacturing">Manufacturing</option>'
                + '<option value="distribution">Distribution</option>';
            var tr = document.createElement('tr');
            tr.innerHTML = '<td><select class="form-select form-select-sm" name="events[' + idx + '][type]">' + typeOptions + '</select></td>'
                + '<td><input type="text" class="form-control form-control-sm" name="events[' + idx + '][date]" placeholder="e.g. ca. 1900"></td>'
                + '<td><input type="date" class="form-control form-control-sm" name="events[' + idx + '][startDate]"></td>'
                + '<td><input type="date" class="form-control form-control-sm" name="events[' + idx + '][endDate]"></td>'
                + '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">Remove</button></td>';
            tbody.appendChild(tr);
        });
    }

    /* ---- Add note row ---- */
    var addNoteBtn = document.getElementById('add-note-row');
    if (addNoteBtn) {
        addNoteBtn.addEventListener('click', function() {
            var tbody = document.querySelector('#notes-table tbody');
            var idx = tbody.querySelectorAll('tr').length;
            var noteTypeOptions = '<option value="">- Select -</option>'
                + '<option value="general">General note</option>'
                + '<option value="conservation">Conservation</option>'
                + '<option value="physical_description">Physical description</option>'
                + '<option value="accompanying_material">Accompanying material</option>'
                + '<option value="alpha_numeric_designation">Alpha-numeric designation</option>'
                + '<option value="rights">Rights</option>'
                + '<option value="language">Language</option>'
                + '<option value="publication">Publication</option>';
            var tr = document.createElement('tr');
            tr.innerHTML = '<td><select class="form-select form-select-sm" name="notes[' + idx + '][type]">' + noteTypeOptions + '</select></td>'
                + '<td><textarea class="form-control form-control-sm" name="notes[' + idx + '][content]" rows="2"></textarea></td>'
                + '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">Remove</button></td>';
            tbody.appendChild(tr);
        });
    }

    /* ---- Add publication note row ---- */
    var addPubNoteBtn = document.getElementById('add-pubnote-row');
    if (addPubNoteBtn) {
        addPubNoteBtn.addEventListener('click', function() {
            var list = document.getElementById('pubnotes-list');
            var idx = list.querySelectorAll('.mb-1').length;
            var div = document.createElement('div');
            div.className = 'mb-1';
            div.innerHTML = '<div class="input-group input-group-sm"><textarea class="form-control form-control-sm" name="publicationNotes[' + idx + '][content]" rows="2"></textarea><button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button></div>';
            list.appendChild(div);
        });
    }

    /* ---- Generic repeatable text row adder ---- */
    var repeatableConfig = [
        { btnId: 'add-creator-row',  listId: 'creators-list',  namePrefix: 'creators',         placeholder: 'Creator name' },
        { btnId: 'add-subject-row',  listId: 'subjects-list',  namePrefix: 'subjects',          placeholder: 'Type to add subject...' },
        { btnId: 'add-place-row',    listId: 'places-list',    namePrefix: 'places',            placeholder: 'Type to add place...' },
        { btnId: 'add-name-row',     listId: 'names-list',     namePrefix: 'nameAccessPoints',  placeholder: 'Type to add name...' },
        { btnId: 'add-genre-row',    listId: 'genres-list',    namePrefix: 'genres',            placeholder: 'Type to add genre...' },
    ];

    repeatableConfig.forEach(function(cfg) {
        var btn = document.getElementById(cfg.btnId);
        if (btn) {
            btn.addEventListener('click', function() {
                var list = document.getElementById(cfg.listId);
                var idx = list.querySelectorAll('.input-group').length;
                var div = document.createElement('div');
                div.className = 'input-group input-group-sm mb-1';
                div.innerHTML = '<input type="text" class="form-control" name="' + cfg.namePrefix + '[' + idx + ']" placeholder="' + cfg.placeholder + '">'
                    + '<button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>';
                list.appendChild(div);
            });
        }
    });

    /* ---- Language and script option maps for dynamic rows ---- */
    var languageOptionsMap = {
        'en': 'English', 'af': 'Afrikaans', 'nl': 'Dutch', 'de': 'German',
        'fr': 'French', 'zu': 'Zulu', 'xh': 'Xhosa', 'st': 'Sesotho',
        'tn': 'Setswana', 'nso': 'Sepedi', 'ts': 'Tsonga', 'ss': 'Swati',
        've': 'Venda', 'nr': 'Ndebele', 'pt': 'Portuguese', 'es': 'Spanish',
        'it': 'Italian', 'la': 'Latin', 'grc': 'Ancient Greek', 'he': 'Hebrew',
        'ar': 'Arabic', 'fa': 'Persian', 'hi': 'Hindi', 'zh': 'Chinese',
        'ja': 'Japanese', 'ko': 'Korean', 'ru': 'Russian', 'sw': 'Swahili'
    };
    var scriptOptionsMap = {
        'Latn': 'Latin', 'Cyrl': 'Cyrillic', 'Arab': 'Arabic', 'Grek': 'Greek',
        'Hebr': 'Hebrew', 'Deva': 'Devanagari', 'Hans': 'Chinese Simplified',
        'Hant': 'Chinese Traditional', 'Jpan': 'Japanese', 'Kore': 'Korean',
        'Thai': 'Thai', 'Geor': 'Georgian', 'Armn': 'Armenian', 'Ethi': 'Ethiopic'
    };

    document.querySelectorAll('.btn-add-lang-row, .btn-add-script-row').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var target = document.getElementById(this.getAttribute('data-target'));
            var name = this.getAttribute('data-name');
            var isScript = btn.classList.contains('btn-add-script-row');
            var optionsMap = isScript ? scriptOptionsMap : languageOptionsMap;
            var placeholder = isScript ? '-- Select script --' : '-- Select language --';
            var div = document.createElement('div');
            div.className = 'input-group input-group-sm mb-1';
            var opts = '<option value="">' + placeholder + '</option>';
            for (var code in optionsMap) {
                opts += '<option value="' + code + '">' + optionsMap[code] + ' (' + code + ')</option>';
            }
            div.innerHTML = '<select class="form-select form-select-sm" name="' + name + '">' + opts + '</select>'
                + '<button type="button" class="btn btn-outline-danger btn-remove-multi">Remove</button>';
            target.appendChild(div);
        });
    });
});
</script>
@endpush
@endsection
