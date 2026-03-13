<?php

namespace App\Http\Requests\Document;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf',
                'max:20480', // 20 MB
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Bitte wählen Sie eine Datei aus.',
            'file.file' => 'Die hochgeladene Datei ist ungültig.',
            'file.mimes' => 'Nur PDF-Dateien sind erlaubt.',
            'file.max' => 'Die maximale Dateigröße beträgt 20 MB.',
        ];
    }
}
