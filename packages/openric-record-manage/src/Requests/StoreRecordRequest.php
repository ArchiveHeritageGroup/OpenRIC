<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordRequest extends FormRequest
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
        ];
    }
}
