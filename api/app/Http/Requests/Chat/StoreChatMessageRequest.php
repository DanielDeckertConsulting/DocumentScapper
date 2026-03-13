<?php

namespace App\Http\Requests\Chat;

use Illuminate\Foundation\Http\FormRequest;

class StoreChatMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Bitte stellen Sie eine Frage.',
            'content.max' => 'Die Frage darf maximal 2000 Zeichen lang sein.',
        ];
    }
}
