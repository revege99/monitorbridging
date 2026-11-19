<?php

namespace App\Http\Controllers\Api\Pdf;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Pdf\PrintHealthCertificateRequest;
use App\Services\LocalClinic;
use App\Services\Pdf\PdfRenderer;
use App\Services\Pdf\SickCertificateService;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class SickCertificateController extends Controller
{
    public function __invoke(PrintHealthCertificateRequest $request, LocalClinic $active, SickCertificateService $service, PdfRenderer $renderer): Response
    {
        try {
            $context = $active->resolveForPdf();
            $active->configureClinicDatabase($context);
        } catch (Throwable $exception) {
            report($exception);
            abort(503, 'Database klinik aktif belum siap untuk mencetak PDF.');
        }

        try {
            $data = $service->data($request->validated('no_rawat'), $request->validated('no_surat'), $context->clinic);

            return $renderer->inline(
                'pdf.documents.sick-certificate',
                $data,
                'surat-keterangan-sakit-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $data['letter_number']).'.pdf',
                'a4',
                'portrait',
            );
        } catch (HttpExceptionInterface|ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            abort(500, 'PDF gagal dibuat. Periksa konfigurasi dan data klinik.');
        }
    }
}
