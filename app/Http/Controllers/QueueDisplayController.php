<?php

namespace App\Http\Controllers;

use App\Models\InstallationSetting;
use App\Models\QueueCallHistory;
use App\Services\ActiveInstallation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QueueDisplayController extends Controller
{
    public function __construct(private readonly ActiveInstallation $activeInstallation)
    {
    }

    public function index(): View
    {
        $installation = $this->installation();

        return view('display.queue', [
            'clinic' => $installation?->clinic,
            'initialState' => $this->statePayload($installation?->clinic_id),
        ]);
    }

    public function state(): JsonResponse
    {
        $installation = $this->installation();

        return response()->json($this->statePayload($installation?->clinic_id));
    }

    public function store(Request $request): JsonResponse
    {
        $configuredToken = (string) config('services.khanza.integration_token');
        $providedToken = (string) $request->header('X-Integration-Token');

        if ($configuredToken === '' || !hash_equals($configuredToken, $providedToken)) {
            return response()->json(['message' => 'Token integrasi tidak valid'], 401);
        }

        $installation = $this->installation();
        if (!$installation?->clinic_id) {
            return response()->json(['message' => 'Instalasi klinik belum diaktifkan'], 422);
        }

        $data = $request->validate([
            'no_rawat' => ['required', 'string', 'max:30'],
            'nama' => ['required', 'string', 'max:255'],
            'nomor' => ['required', 'string', 'max:30'],
            'dokter' => ['nullable', 'string', 'max:255'],
            'spesialis' => ['nullable', 'string', 'max:255'],
            'sumber' => ['nullable', 'string', 'in:panggil,panggil_next,panggil_skip'],
        ]);

        $this->activeInstallation->configureClinicDatabase($installation);
        $calledAt = now('Asia/Jakarta');

        $call = QueueCallHistory::create([
            'no_rawat' => trim($data['no_rawat']),
            'no_antrean' => trim($data['nomor']),
            'nama_pasien' => trim($data['nama']),
            'nama_dokter' => trim($data['dokter'] ?? ''),
            'nama_poli' => trim($data['spesialis'] ?? ''),
            'sumber_panggilan' => $data['sumber'] ?? 'panggil',
            'waktu_panggil' => $calledAt,
        ]);

        return response()->json([
            'message' => 'Panggilan berhasil ditampilkan',
            'data' => $this->serialize($call),
        ]);
    }

    private function installation(): ?InstallationSetting
    {
        return InstallationSetting::with(['clinic', 'clinic.database'])->where('is_activated', true)->first();
    }

    private function statePayload(?int $clinicId): array
    {
        if (!$clinicId) {
            return ['latest' => null, 'recent' => [], 'calls' => [], 'polis' => [], 'server_time' => now('Asia/Jakarta')->toIso8601String()];
        }

        $installation = $this->installation();
        if (!$installation?->clinic?->database) {
            return ['latest' => null, 'recent' => [], 'calls' => [], 'polis' => [], 'server_time' => now('Asia/Jakarta')->toIso8601String()];
        }

        $this->activeInstallation->configureClinicDatabase($installation);
        $todayCalls = QueueCallHistory::whereDate('waktu_panggil', now('Asia/Jakarta')->toDateString())
            ->latest('waktu_panggil')->latest('id')->get();
        $calls = $todayCalls->take(8);

        return [
            'latest' => $calls->first() ? $this->serialize($calls->first()) : null,
            'recent' => $calls->skip(1)->take(6)->map(fn (QueueCallHistory $call) => $this->serialize($call))->values(),
            'calls' => $todayCalls->take(20)->map(fn (QueueCallHistory $call) => $this->serialize($call))->values(),
            'polis' => $this->scheduledPolis($todayCalls),
            'server_time' => now('Asia/Jakarta')->toIso8601String(),
        ];
    }

    private function scheduledPolis($todayCalls): array
    {
        $day = [
            'Monday' => 'SENIN', 'Tuesday' => 'SELASA', 'Wednesday' => 'RABU',
            'Thursday' => 'KAMIS', 'Friday' => 'JUMAT', 'Saturday' => 'SABTU', 'Sunday' => 'AKHAD',
        ][now('Asia/Jakarta')->englishDayOfWeek];

        $polis = DB::connection('clinic')->table('jadwal as j')
            ->join('poliklinik as p', 'p.kd_poli', '=', 'j.kd_poli')
            ->where('j.hari_kerja', $day)
            ->where('p.status', '1')
            ->select(['p.kd_poli', 'p.nm_poli'])
            ->distinct()->orderBy('p.nm_poli')->get();

        $lastCallsByPoli = $todayCalls->unique(fn (QueueCallHistory $call) => mb_strtolower(trim($call->nama_poli ?? '')));

        return $polis->map(function ($poli) use ($lastCallsByPoli) {
            $lastCall = $lastCallsByPoli->first(fn (QueueCallHistory $call) => mb_strtolower(trim($call->nama_poli ?? '')) === mb_strtolower(trim($poli->nm_poli)));

            return [
                'kode' => $poli->kd_poli,
                'nama' => $poli->nm_poli,
                'nomor' => $lastCall?->no_antrean,
                'waktu' => $lastCall?->waktu_panggil?->format('H:i'),
            ];
        })->values()->all();
    }

    private function serialize(QueueCallHistory $call): array
    {
        return [
            'id' => $call->id,
            'no_rawat' => $call->no_rawat,
            'nomor' => $call->no_antrean,
            'nama' => $call->nama_pasien,
            'dokter' => $call->nama_dokter,
            'spesialis' => $call->nama_poli,
            'sumber' => $call->sumber_panggilan,
            'waktu' => $call->waktu_panggil?->format('H:i'),
        ];
    }
}
