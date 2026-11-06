<?php

namespace App\Services\Pdf;

use Barryvdh\DomPDF\PDF;
use Illuminate\Http\Response;

class PdfRenderer
{
    public function inline(string $view, array $data, string $filename, string $paper = 'a4', string $orientation = 'portrait'): Response
    {
        // Instance baru untuk setiap dokumen; akses jaringan dan kode aktif dimatikan.
        $pdf = app(PDF::class);
        $pdf->setOptions([
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'isJavascriptEnabled' => false,
            'defaultFont' => 'Helvetica',
        ]);

        return $pdf->loadView($view, $data)->setPaper($paper, $orientation)->stream($filename)
            ->header('Cache-Control', 'private, no-store');
    }
}
