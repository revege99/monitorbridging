<?php

namespace App\Http\Requests\Api\Pdf;

use Illuminate\Foundation\Http\FormRequest;

class PrintPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autentikasi ditangani middleware pada grup route PDF.
        return true;
    }

    public function rules(): array
    {
        return [
            'no_rawat' => ['required', 'string', 'max:17'],
        ];
    }
}
