<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_id' => ['nullable', 'uuid', 'exists:documents,id'],
            'title' => ['nullable', 'string', 'max:500'],
        ];
    }
}
