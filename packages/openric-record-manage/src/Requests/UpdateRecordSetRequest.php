<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRecordSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:1000'],
            'identifier' => ['nullable', 'string', 'max:255'],
            'scope_and_content' => ['nullable', 'string'],
            'parent_iri' => ['nullable', 'string', 'url'],
            'level' => ['required', 'in:fonds,subfonds,series,subseries,file,subfile'],
            'date_begin' => ['nullable', 'regex:/^\d{4}(-\d{2}(-\d{2})?)?$/'],
            'date_end' => ['nullable', 'regex:/^\d{4}(-\d{2}(-\d{2})?)?$/'],
            'date_expression' => ['nullable', 'string', 'max:500'],
            'extent' => ['nullable', 'string'],
        ];
    }
}
