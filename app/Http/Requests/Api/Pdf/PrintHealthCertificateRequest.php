<?php

namespace App\Http\Requests\Api\Pdf;

class PrintHealthCertificateRequest extends PrintPdfRequest
{
    public function rules(): array
    {
        return [
            'no_rawat' => ['required', 'string', 'max:17'],
            'no_surat' => ['nullable', 'string', 'max:17'],
        ];
    }
}
