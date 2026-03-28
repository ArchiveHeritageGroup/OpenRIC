<?php

declare(strict_types=1);

namespace OpenRiC\RecordManage\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecordPartRequest extends FormRequest
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
            'parent_iri' => ['nullable', 'string', 'url'],
        ];
    }
}
