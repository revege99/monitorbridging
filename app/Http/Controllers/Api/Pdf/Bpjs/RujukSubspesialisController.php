<?php

namespace App\Http\Controllers\Api\Pdf\Bpjs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Pdf\PrintPdfRequest;
use App\Services\LocalClinic;
use App\Services\Pdf\Bpjs\RujukSubspesialisService;
use App\Services\Pdf\PdfRenderer;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class RujukSubspesialisController extends Controller
{
    public function __invoke(PrintPdfRequest $request, LocalClinic $active, RujukSubspesialisService $service, PdfRenderer $renderer): Response
    {
        try {
            $installation = $active->resolveForPdf();
            $active->configureClinicDatabase($installation);
        } catch (Throwable $exception) {
            report($exception);
            abort(503, 'Database klinik aktif belum siap untuk mencetak PDF.');
        }

        try {
            $noRawat = $request->validated('no_rawat');
            $data = $service->data($noRawat, $installation->clinic);
            $filename = 'rujukSubspesialis-'.preg_replace('/[^A-Za-z0-9_-]/', '-', $noRawat).'.pdf';

            return $renderer->inline(
                'pdf.documents.bpjs.rujukSubspesialis',
                $data,
                $filename,
                config('pdf.documents.rujukSubspesialis.paper', 'a4'),
                config('pdf.documents.rujukSubspesialis.orientation', 'portrait'),
            );
        } catch (HttpExceptionInterface|ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);
            abort(500, 'PDF gagal dibuat. Periksa konfigurasi dan data klinik.');
        }
    }
}
