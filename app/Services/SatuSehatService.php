<?php

namespace App\Services;

use App\Services\ClinicContext;
use App\Models\SatuSehatBaseUrlConfiguration;
use App\Models\SatuSehatConfiguration;
use App\Models\ServiceLog;
use App\Models\ServiceRun;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class SatuSehatService
{
    private string $organization;

    private string $location;

    private array $identities = [];

    private array $tables = [];

    private ClinicContext $installation;

    private ServiceRun $run;

    private $output;

    public function __construct(private SatuSehatClient $client) {}

    public function configure(ClinicContext $installation): void
    {
        if (! Schema::connection('clinic')->hasTable('ss_config')) {
            throw new SatuSehatConfigurationException('Jalankan database/sql/ss_config.sql pada database SIMRS aktif, lalu isi Konfigurasi > Satu Sehat.');
        }
        $credentials = SatuSehatConfiguration::orderBy('id')->first();
        $urls = SatuSehatBaseUrlConfiguration::first();
        if (! $credentials || ! $urls) {
            throw new SatuSehatConfigurationException('Lengkapi Konfigurasi > Satu Sehat dan Base URL Satu Sehat.');
        }
        $this->client->configure($credentials, $urls);
        $this->organization = $credentials->organization_id;
        $this->location = $credentials->location_id;
        $this->identities = [];
        $this->tables = [];
        if (! Schema::connection('clinic')->hasTable('satu_sehat_service_resources')) {
            throw new SatuSehatConfigurationException('Jalankan database/sql/satu_sehat_service_resources.sql pada database SIMRS aktif.');
        }
    }

    public function scan(ClinicContext $installation, ServiceRun $run, string $from, string $to, callable $output): void
    {
        $this->installation = $installation;
        $this->run = $run;
        $this->output = $output;
        $this->client->authenticate();
        $query = DB::connection('clinic')->table('reg_periksa as rp')
            ->join('pasien as p', 'p.no_rkm_medis', '=', 'rp.no_rkm_medis')
            ->join('dokter as d', 'd.kd_dokter', '=', 'rp.kd_dokter')
            ->join('pegawai as pg', 'pg.nik', '=', 'd.kd_dokter')
            ->join('poliklinik as pl', 'pl.kd_poli', '=', 'rp.kd_poli')
            ->whereBetween('rp.tgl_registrasi', [$from, $to])
            ->whereIn('rp.status_lanjut', ['Ralan', 'Ranap'])
            ->where(fn ($query) => $query->whereNull('rp.stts')->orWhere('rp.stts', '<>', 'Batal'))
            ->select(['rp.no_rawat', 'rp.tgl_registrasi', 'rp.jam_reg', 'rp.status_lanjut', 'rp.kd_poli',
                'p.nm_pasien', 'p.no_ktp as nik_pasien', 'pg.no_ktp as nik_dokter', 'd.nm_dokter', 'pl.nm_poli']);

        // Keyset pagination menjaga penggunaan memori saat backfill.
        $query->chunkById(100, function ($rows) {
            $this->identities = [];
            foreach ($rows as $row) {
                $this->run->update(['last_heartbeat_at' => now()]);
                $this->run->increment('processed');
                $this->terminal('heading', str_repeat('-', 72));
                $this->terminal('heading', 'Nama     : '.$row->nm_pasien);
                $this->terminal('heading', 'No Rawat : '.$row->no_rawat);
                $this->terminal('heading', 'Tanggal  : '.$row->tgl_registrasi.' | Poli: '.$row->nm_poli);
                try {
                    $this->visit((array) $row);
                } catch (Throwable $e) {
                    $this->run->increment('failed');
                    // Jangan tampilkan SQL, NIK, payload, token, atau response klinis.
                    $this->log('error', 'failed', $row->no_rawat, 'Kunjungan gagal diproses; periksa skema, mapping, dan koneksi SIMRS.');
                } finally {
                    $this->terminal('heading', '');
                }
            }
        }, 'rp.no_rawat', 'no_rawat');
    }

    private function visit(array $row): void
    {
        $no = $row['no_rawat'];
        $patient = $this->identity('Patient', $row['nik_pasien']);
        $practitioner = $this->identity('Practitioner', $row['nik_dokter']);
        $start = $this->time($row['tgl_registrasi'], $row['jam_reg']);
        $location = $this->location;
        if ($row['status_lanjut'] === 'Ralan' && $this->hasTable('satu_sehat_mapping_lokasi_ralan')) {
            $location = DB::connection('clinic')->table('satu_sehat_mapping_lokasi_ralan')
                ->where('kd_poli', $row['kd_poli'])->value('id_lokasi_satusehat') ?: $location;
        }
        $payload = [
            'resourceType' => 'Encounter', 'status' => 'arrived',
            'class' => ['system' => 'http://terminology.hl7.org/CodeSystem/v3-ActCode',
                'code' => $row['status_lanjut'] === 'Ranap' ? 'IMP' : 'AMB',
                'display' => $row['status_lanjut'] === 'Ranap' ? 'inpatient encounter' : 'ambulatory'],
            'identifier' => [$this->identifier('encounter', $no)],
            'subject' => ['reference' => "Patient/{$patient}"],
            'participant' => [['type' => [$this->concept('http://terminology.hl7.org/CodeSystem/v3-ParticipationType', 'ATND', 'attender')],
                'individual' => ['reference' => "Practitioner/{$practitioner}"]]],
            'period' => ['start' => $start], 'statusHistory' => [['status' => 'arrived', 'period' => ['start' => $start]]],
            'location' => [['location' => ['reference' => "Location/{$location}", 'display' => $row['nm_poli']]]],
            'serviceProvider' => ['reference' => 'Organization/'.$this->organization],
        ];
        $encounter = $this->send($no, 'Encounter', 'encounter', $payload,
            ['satu_sehat_encounter_new', ['no_rawat' => $no], 'id_encounter', ['patient_id' => $patient], 'success']);
        if (! $encounter) {
            return;
        }
        $context = ['subject' => ['reference' => "Patient/{$patient}"], 'encounter' => ['reference' => "Encounter/{$encounter}"]];
        // Setiap tahap independen: belum ada diagnosa tidak menghalangi tanda vital/obat.
        foreach (['conditions' => 'Condition', 'observations' => 'Observation', 'procedures' => 'Procedure',
            'clinical' => 'ClinicalImpression', 'medicationRequests' => 'MedicationRequest',
            'medicationDispenses' => 'MedicationDispense', 'medicationStatements' => 'MedicationStatement',
            'carePlans' => 'CarePlan'] as $stage => $label) {
            try {
                $this->$stage($row, $context, $practitioner);
            } catch (Throwable $e) {
                $this->run->increment('failed');
                $this->log('warning', 'failed', $no, "{$label}: belum dapat diproses; periksa data dan tabel pendukung.");
            }
        }
    }

    private function identity(string $type, string $nik): string
    {
        $nik = trim($nik);
        if (! preg_match('/^\d{16}$/D', $nik)) {
            throw new RuntimeException('NIK tidak valid.');
        }
        $key = $type.':'.$nik;
        if (! isset($this->identities[$key])) {
            $resource = $this->client->search($type, ['identifier' => 'https://fhir.kemkes.go.id/id/nik|'.$nik]);
            if (blank($resource['id'] ?? null)) {
                // Identitas harus terdaftar di MPI/master nakes; jangan membuat identitas minimal/fiktif.
                throw new RuntimeException("Identitas {$type} belum terdaftar.");
            }
            $this->identities[$key] = $resource['id'];
        }

        return $this->identities[$key];
    }

    private function conditions(array $row, array $context, string $practitioner): void
    {
        $no = $row['no_rawat'];
        $diagnoses = DB::connection('clinic')->table('diagnosa_pasien as dp')
            ->join('penyakit as p', 'p.kd_penyakit', '=', 'dp.kd_penyakit')
            ->where('dp.no_rawat', $no)->where('dp.status', $row['status_lanjut'])
            ->select('dp.kd_penyakit', 'p.nm_penyakit')->distinct()->get();
        foreach ($diagnoses as $diag) {
            $payload = $context + [
                'resourceType' => 'Condition',
                'clinicalStatus' => $this->concept('http://terminology.hl7.org/CodeSystem/condition-clinical', 'active', 'Active'),
                'category' => [$this->concept('http://terminology.hl7.org/CodeSystem/condition-category', 'encounter-diagnosis', 'Encounter Diagnosis')],
                'code' => $this->concept('http://hl7.org/fhir/sid/icd-10', $diag->kd_penyakit, $diag->nm_penyakit),
            ];
            $this->send($no, 'Condition', 'condition:'.$diag->kd_penyakit, $payload,
                ['satu_sehat_condition_new', ['no_rawat' => $no, 'kd_penyakit' => $diag->kd_penyakit], 'id_condition', [], 'berhasil']);
        }
    }

    private function examinations(string $no, string $status)
    {
        return DB::connection('clinic')->table($status === 'Ranap' ? 'pemeriksaan_ranap' : 'pemeriksaan_ralan')->where('no_rawat', $no)
            ->orderBy('tgl_perawatan')->orderBy('jam_rawat')->get();
    }

    private function observations(array $row, array $context, string $practitioner): void
    {
        $specs = [
            'suhu_tubuh' => ['suhu', '8310-5', 'Body temperature', 'Cel'],
            'respirasi' => ['respirasi', '9279-1', 'Respiratory rate', '/min'],
            'nadi' => ['nadi', '8867-4', 'Heart rate', '/min'],
            'spo2' => ['spo2', '59408-5', 'Oxygen saturation in Arterial blood by Pulse oximetry', '%'],
            'gcs' => ['gcs', '9269-2', 'Glasgow coma score total', '{score}'],
            'tinggi' => ['tinggi', '8302-2', 'Body height', 'cm'],
            'berat' => ['berat', '29463-7', 'Body weight', 'kg'],
            'lingkar_perut' => ['lingkarperut', '8280-0', 'Waist Circumference at umbilicus by Tape measure', 'cm'],
        ];
        foreach ($this->examinations($row['no_rawat'], $row['status_lanjut'])->take(1) as $exam) {
            $exam = (array) $exam;
            $at = $this->time($exam['tgl_perawatan'], $exam['jam_rawat']);
            $base = $context + ['resourceType' => 'Observation', 'status' => 'final',
                'category' => [$this->concept('http://terminology.hl7.org/CodeSystem/observation-category', 'vital-signs', 'Vital Signs')],
                'performer' => [['reference' => "Practitioner/{$practitioner}"]], 'effectiveDateTime' => $at, 'issued' => $at];
            foreach ($specs as $field => [$suffix, $code, $display, $unit]) {
                $value = $this->number($exam[$field] ?? null);
                if ($value === null || $value <= 0) {
                    continue;
                }
                $payload = $base + ['code' => $this->concept('http://loinc.org', $code, $display),
                    'valueQuantity' => ['value' => $value, 'unit' => $unit, 'system' => 'http://unitsofmeasure.org', 'code' => $unit]];
                $this->send($row['no_rawat'], 'Observation', "observation:{$suffix}:{$at}", $payload,
                    ['satu_sehat_observationttv'.$suffix.'_new', ['no_rawat' => $row['no_rawat']], 'observation_id', [$suffix === 'lingkarperut' ? 'lingkar_perut' : $suffix => $value], 'berhasil']);
            }
            if (preg_match('/^\s*(\d{2,3})\s*\/\s*(\d{2,3})\s*$/', $exam['tensi'] ?? '', $matches)) {
                $payload = $base + ['code' => $this->concept('http://loinc.org', '85354-9', 'Blood pressure panel with all children optional')];
                foreach ([['8480-6', 'Systolic blood pressure', (float) $matches[1]], ['8462-4', 'Diastolic blood pressure', (float) $matches[2]]] as [$code, $display, $value]) {
                    $payload['component'][] = ['code' => $this->concept('http://loinc.org', $code, $display),
                        'valueQuantity' => ['value' => $value, 'unit' => 'mmHg', 'system' => 'http://unitsofmeasure.org', 'code' => 'mm[Hg]']];
                }
                $this->send($row['no_rawat'], 'Observation', 'observation:tensi:'.$at, $payload,
                    ['satu_sehat_observationttvtensi_new', ['no_rawat' => $row['no_rawat']], 'observation_id',
                        ['sistole' => (float) $matches[1], 'diastole' => (float) $matches[2]], 'berhasil']);
            }
            if (filled($exam['kesadaran'] ?? null) && $exam['kesadaran'] !== '-') {
                $payload = $base;
                $payload['category'] = [$this->concept('http://terminology.hl7.org/CodeSystem/observation-category', 'exam', 'Exam')];
                $payload['code'] = $this->concept('http://snomed.info/sct', '1104441000000107', 'ACVPU (Alert Confusion Voice Pain Unresponsive) scale score');
                $payload['valueCodeableConcept'] = ['text' => $exam['kesadaran']];
                $this->send($row['no_rawat'], 'Observation', 'observation:kesadaran:'.$at, $payload,
                    ['satu_sehat_observationttvkesadaran_new', ['no_rawat' => $row['no_rawat']], 'observation_id', ['kesadaran' => $exam['kesadaran']], 'berhasil']);
            }
        }
    }

    private function procedures(array $row, array $context, string $practitioner): void
    {
        $procedures = DB::connection('clinic')->table('prosedur_pasien as pp')->join('icd9 as ic', 'ic.kode', '=', 'pp.kode')
            ->where('pp.no_rawat', $row['no_rawat'])->where('pp.status', $row['status_lanjut'])
            ->select('pp.kode', 'ic.deskripsi_panjang')->distinct()->get();
        foreach ($procedures as $procedure) {
            $payload = $context + ['resourceType' => 'Procedure', 'status' => 'completed',
                'category' => $this->concept('http://snomed.info/sct', '103693007', 'Diagnostic procedure'),
                'code' => $this->concept('http://hl7.org/fhir/sid/icd-9-cm', $procedure->kode, $procedure->deskripsi_panjang),
                'performer' => [['actor' => ['reference' => "Practitioner/{$practitioner}"]]]];
            // Registrasi bukan waktu tindakan: jangan mengarang performedPeriod.
            $this->send($row['no_rawat'], 'Procedure', 'procedure:'.$procedure->kode, $payload,
                ['satu_sehat_procedure_new', ['no_rawat' => $row['no_rawat'], 'kode' => $procedure->kode], 'procedure_id', [], 'berhasil']);
        }
    }

    private function clinical(array $row, array $context, string $practitioner): void
    {
        foreach ($this->examinations($row['no_rawat'], $row['status_lanjut'])->take(1) as $exam) {
            if (blank($exam->penilaian)) {
                continue;
            }
            $at = $this->time($exam->tgl_perawatan, $exam->jam_rawat);
            $payload = $context + ['resourceType' => 'ClinicalImpression', 'status' => 'completed',
                'description' => $exam->penilaian, 'summary' => $exam->penilaian, 'effectiveDateTime' => $at,
                'date' => $at, 'assessor' => ['reference' => "Practitioner/{$practitioner}"]];
            foreach (DB::connection('clinic')->table('diagnosa_pasien')->where('no_rawat', $row['no_rawat'])
                ->where('status', $row['status_lanjut'])->pluck('kd_penyakit') as $diagnosis) {
                $conditionKey = hash('sha256', $this->organization.'|'.$row['no_rawat'].'|condition:'.$diagnosis);
                $condition = DB::connection('clinic')->table('satu_sehat_service_resources')
                    ->where('resource_key', $conditionKey)->where('status', 'success')->value('resource_id');
                if ($condition) {
                    $payload['finding'][] = ['itemReference' => ['reference' => 'Condition/'.$condition]];
                }
            }
            $this->send($row['no_rawat'], 'ClinicalImpression', 'clinical:'.$at, $payload,
                ['satu_sehat_clinicalimpression_new', ['no_rawat' => $row['no_rawat']], 'clinicalimpression_id', [], 'berhasil']);
        }
    }

    private function carePlans(array $row, array $context, string $practitioner): void
    {
        foreach ($this->examinations($row['no_rawat'], $row['status_lanjut']) as $exam) {
            if (blank($exam->rtl)) {
                continue;
            }
            $author = $this->identity('Practitioner', (string) DB::connection('clinic')->table('pegawai')->where('nik', $exam->nip)->value('no_ktp'));
            $at = $this->time($exam->tgl_perawatan, $exam->jam_rawat);
            $payload = $context + ['resourceType' => 'CarePlan', 'identifier' => [$this->identifier('careplan', $row['no_rawat'].':'.$at)],
                'title' => 'Instruksi Medik dan Keperawatan Pasien', 'status' => 'active', 'intent' => 'plan',
                'category' => [$this->concept('http://snomed.info/sct',
                    $row['status_lanjut'] === 'Ranap' ? '736353004' : '736271009',
                    $row['status_lanjut'] === 'Ranap' ? 'Inpatient care plan' : 'Outpatient care plan')],
                'description' => $exam->rtl, 'created' => $at, 'author' => ['reference' => "Practitioner/{$author}"]];
            $this->send($row['no_rawat'], 'CarePlan', 'careplan:'.$at, $payload,
                ['satu_sehat_careplan_new', ['no_rawat' => $row['no_rawat'], 'tgl_perawatan' => $exam->tgl_perawatan, 'jam_rawat' => $exam->jam_rawat], 'id_careplan', [], 'Berhasil']);
        }
    }

    private function medicationRequests(array $row, array $context, string $practitioner): void
    {
        $this->medications($row, $context, $practitioner, 'request');
    }

    private function medicationDispenses(array $row, array $context, string $practitioner): void
    {
        $this->medications($row, $context, $practitioner, 'dispense');
    }

    private function medicationStatements(array $row, array $context, string $practitioner): void
    {
        $this->medications($row, $context, $practitioner, 'statement');
    }

    private function medications(array $row, array $context, string $practitioner, string $phase): void
    {
        $items = DB::connection('clinic')->table('resep_obat as ro')
            ->join('resep_dokter as rd', 'rd.no_resep', '=', 'ro.no_resep')
            ->join('satu_sehat_mapping_obat as mo', 'mo.kode_brng', '=', 'rd.kode_brng')
            ->join('satu_sehat_medication as sm', 'sm.kode_brng', '=', 'rd.kode_brng')
            ->join('pegawai as pg', 'pg.nik', '=', 'ro.kd_dokter')
            ->where('ro.no_rawat', $row['no_rawat'])
            ->select(['ro.*', 'rd.kode_brng', 'rd.jml', 'rd.aturan_pakai', 'mo.*', 'sm.id_medication', 'pg.no_ktp as prescriber_nik'])->get();
        foreach ($items as $item) {
            $item = (array) $item;
            if (blank($item['id_medication']) || $this->number($item['jml']) === null || (float) $item['jml'] <= 0) {
                continue;
            }
            $author = $this->identity('Practitioner', $item['prescriber_nik']);
            $no = $row['no_rawat'];
            $key = ['no_rawat' => $no, 'no_resep' => $item['no_resep'], 'kode_brng' => $item['kode_brng']];
            $itemKey = $item['no_resep'].':'.$item['kode_brng'];
            $medication = ['reference' => 'Medication/'.$item['id_medication'], 'display' => $item['obat_display']];
            $dosage = $this->dosage($item, $item['aturan_pakai']);
            $payload = $context + ['resourceType' => 'MedicationRequest', 'status' => 'completed', 'intent' => 'order',
                'identifier' => [$this->identifier('prescription', $item['no_resep']), $this->identifier('prescription-item', $itemKey)],
                'category' => [$this->concept('http://terminology.hl7.org/CodeSystem/medicationrequest-category',
                    $row['status_lanjut'] === 'Ranap' ? 'inpatient' : 'outpatient',
                    $row['status_lanjut'] === 'Ranap' ? 'Inpatient' : 'Outpatient')],
                'medicationReference' => $medication, 'authoredOn' => $this->time($item['tgl_peresepan'], $item['jam_peresepan']),
                'requester' => ['reference' => "Practitioner/{$author}"], 'dosageInstruction' => [$dosage],
                'dispenseRequest' => ['quantity' => $this->quantity($item, (float) $item['jml']), 'performer' => ['reference' => 'Organization/'.$this->organization]]];
            if ($phase === 'request') {
                $this->send($no, 'MedicationRequest', 'medicationrequest:'.$itemKey, $payload,
                    ['satu_sehat_medicationrequest_new', $key, 'id_medicationrequest', [], 'Berhasil']);

                continue;
            }
            $request = DB::connection('clinic')->table('satu_sehat_service_resources')
                ->where('resource_key', hash('sha256', $this->organization.'|'.$no.'|medicationrequest:'.$itemKey))
                ->where('status', 'success')->value('resource_id');
            if (! $request) {
                continue;
            }
            if ($phase === 'dispense') {
                $this->dispenses($row, $context, $item, $request, $medication);

                continue;
            }
            // Jangan menyatakan pasien telah memahami obat hanya dari keberadaan resep.
            // MedicationStatement hanya dibuat setelah ada tanggal penyerahan nyata.
            if (filled($item['tgl_penyerahan'] ?? null) && $item['tgl_penyerahan'] !== '0000-00-00') {
                $statement = ['resourceType' => 'MedicationStatement', 'status' => 'completed',
                    'identifier' => [$this->identifier('medicationstatement', $itemKey)],
                    'subject' => $context['subject'], 'context' => $context['encounter'], 'medicationReference' => $medication,
                    'dateAsserted' => $this->time($item['tgl_penyerahan'], $item['jam_penyerahan']), 'dosage' => [$dosage]];
                $this->send($no, 'MedicationStatement', 'medicationstatement:'.$itemKey, $statement,
                    ['satu_sehat_medicationstatement_new', $key, 'id_medicationstatement', [], 'Berhasil']);
            }
        }
    }

    private function dispenses(array $row, array $context, array $item, string $request, array $medication): void
    {
        $dispenses = DB::connection('clinic')->table('detail_pemberian_obat as d')
            ->join('aturan_pakai as ap', function ($join) {
                $join->on('ap.no_rawat', '=', 'd.no_rawat')->on('ap.kode_brng', '=', 'd.kode_brng')
                    ->on('ap.tgl_perawatan', '=', 'd.tgl_perawatan')->on('ap.jam', '=', 'd.jam');
            })->join('satu_sehat_mapping_lokasi_depo_farmasi as ml', 'ml.kd_bangsal', '=', 'd.kd_bangsal')
            ->where('d.no_rawat', $row['no_rawat'])->where('d.kode_brng', $item['kode_brng'])
            ->where('d.tgl_perawatan', $item['tgl_perawatan'])->where('d.jam', $item['jam'])
            ->select('d.*', 'ap.aturan', 'ml.id_lokasi_satusehat')->get();
        foreach ($dispenses as $dispense) {
            $dispense = (array) $dispense;
            $key = array_intersect_key($dispense, array_flip(['no_rawat', 'tgl_perawatan', 'jam', 'kode_brng', 'no_batch', 'no_faktur']));
            $value = implode(':', array_values($key));
            if (blank($dispense['id_lokasi_satusehat']) || (float) $dispense['jml'] <= 0) {
                continue;
            }
            $payload = ['resourceType' => 'MedicationDispense', 'status' => 'completed',
                'identifier' => [$this->identifier('medicationdispense', hash('sha256', $value))],
                'subject' => $context['subject'], 'context' => $context['encounter'], 'medicationReference' => $medication,
                'authorizingPrescription' => [['reference' => "MedicationRequest/{$request}"]],
                'location' => ['reference' => 'Location/'.$dispense['id_lokasi_satusehat']],
                'quantity' => $this->quantity($item, (float) $dispense['jml']),
                'whenPrepared' => $this->time($item['tgl_peresepan'], $item['jam_peresepan']),
                'whenHandedOver' => $this->time($dispense['tgl_perawatan'], $dispense['jam']),
                'dosageInstruction' => [$this->dosage($item, $dispense['aturan'])]];
            // Petugas penyerahan tidak tersedia dari query lama: jangan mengaku dokter sebagai petugas farmasi.
            $this->send($row['no_rawat'], 'MedicationDispense', 'medicationdispense:'.$value, $payload,
                ['satu_sehat_medicationdispense_new', $key, 'id_medicationdispanse', ['no_resep' => $item['no_resep']], 'Berhasil']);
        }
    }

    private function dosage(array $item, string $instructions): array
    {
        $dosage = ['text' => $instructions];
        // Jumlah obat yang diresepkan bukan dosis per pemberian.
        if (preg_match('/^\s*(\d+)\s*[xX]\s*(\d+(?:[.,]\d+)?)\s*$/D', $instructions, $match)
            && (int) $match[1] > 0 && (float) str_replace(',', '.', $match[2]) > 0) {
            $dosage['timing'] = ['repeat' => ['frequency' => (int) $match[1], 'period' => 1, 'periodUnit' => 'd']];
            $dosage['doseAndRate'] = [['doseQuantity' => $this->quantity($item, (float) str_replace(',', '.', $match[2]))]];
        }
        if (filled($item['route_system'] ?? null) && filled($item['route_code'] ?? null)) {
            $dosage['route'] = $this->concept($item['route_system'], $item['route_code'], $item['route_display']);
        }

        return $dosage;
    }

    private function quantity(array $item, float $value): array
    {
        if (blank($item['denominator_code'] ?? null) || blank($item['denominator_system'] ?? null)) {
            throw new RuntimeException('Mapping satuan obat belum lengkap.');
        }

        return ['value' => $value, 'unit' => $item['denominator_code'], 'system' => $item['denominator_system'], 'code' => $item['denominator_code']];
    }

    private function send(string $no, string $type, string $localKey, array $payload, array $legacy): ?string
    {
        $label = $this->resourceLabel($type, $localKey);
        $this->run->update(['last_heartbeat_at' => now()]);
        $key = hash('sha256', $this->organization.'|'.$no.'|'.$localKey);
        $journal = DB::connection('clinic')->table('satu_sehat_service_resources');
        $existing = (clone $journal)->where('resource_key', $key)->first();
        if ($existing?->status === 'success') {
            $this->mirror($legacy, $existing->resource_id);
            $this->terminal('success', "{$label} | SUDAH TERKIRIM | ID: {$existing->resource_id}");

            return $existing->resource_id;
        }
        // Import hasil sukses worker lama; tidak membuat ulang resource yang sudah terkirim.
        [$table, $where, $idColumn] = $legacy;
        if ($this->hasTable($table)) {
            $old = DB::connection('clinic')->table($table)->where($where)->first();
            if ($old && in_array(strtolower($old->status ?? ''), ['success', 'berhasil'], true) && filled($old->$idColumn)) {
                if ($type === 'Encounter' && 'Patient/'.($old->patient_id ?? '') !== $payload['subject']['reference']) {
                    throw new RuntimeException('Pasien encounter lama tidak cocok dengan hasil MPI.');
                }
                $this->record($key, $no, $type, 'success', $old->$idColumn);
                $this->terminal('success', "{$label} | SUDAH TERKIRIM (data lama) | ID: {$old->$idColumn}");

                return $old->$idColumn;
            }
        }
        if ($existing && in_array($existing->status, ['pending', 'uncertain'], true)) {
            $this->terminal('warning', "{$label} | MENUNGGU REKONSILIASI ({$existing->status})");

            return null;
        }
        if ($existing && $existing->last_attempt_at && CarbonImmutable::parse($existing->last_attempt_at, 'Asia/Jakarta')->addMinutes(5)->isFuture()) {
            $this->terminal('warning', "{$label} | MENUNGGU JEDA COBA ULANG");

            return null;
        }
        // Encounter dapat direkonsiliasi lewat identifier sebelum POST.
        if ($type === 'Encounter') {
            $identifier = $payload['identifier'][0];
            $remote = $this->client->search($type, ['identifier' => $identifier['system'].'|'.$identifier['value']]);
            if (filled($remote['id'] ?? null)) {
                if (($remote['subject']['reference'] ?? '') !== $payload['subject']['reference']) {
                    throw new RuntimeException('Encounter remote tidak cocok dengan pasien.');
                }
                $this->record($key, $no, $type, 'success', $remote['id']);
                $this->mirror($legacy, $remote['id']);
                $this->terminal('success', "{$label} | SUDAH TERKIRIM (ditemukan di server) | ID: {$remote['id']}");

                return $remote['id'];
            }
        }
        $this->client->authenticate();
        $now = now('Asia/Jakarta');
        $claimed = $journal->insertOrIgnore(['resource_key' => $key, 'no_rawat' => $no, 'resource_type' => $type,
            'status' => 'pending', 'attempts' => 1, 'last_attempt_at' => $now, 'created_at' => $now, 'updated_at' => $now]);
        if (! $claimed) {
            $claimed = DB::connection('clinic')->table('satu_sehat_service_resources')
                ->where('resource_key', $key)->where('status', 'failed')->where('last_attempt_at', '<=', $now->copy()->subMinutes(5))
                ->update(['status' => 'pending', 'attempts' => DB::raw('attempts + 1'), 'last_attempt_at' => $now, 'updated_at' => $now]);
        }
        if (! $claimed) {
            return null;
        }
        try {
            $this->terminal('info', "{$label} | MENGIRIM");
            $response = $this->client->create($type, $payload);
        } catch (Throwable $e) {
            $this->record($key, $no, $type, 'uncertain');
            $this->run->increment('failed');
            $this->log('warning', 'uncertain', $no, "{$label}: hasil pengiriman belum pasti; perlu rekonsiliasi.");

            return null;
        }
        $id = $response->json('id');
        $success = $response->status() === 201 && is_string($id) && $id !== '' && $response->json('resourceType') === $type;
        $status = $success ? 'success' : (($response->status() >= 500 || $response->successful() || in_array($response->status(), [408, 409], true)) ? 'uncertain' : 'failed');
        $this->record($key, $no, $type, $status, $success ? $id : null, $response->status());
        $this->run->increment($success ? 'succeeded' : 'failed');
        $issues = [];
        foreach ((array) $response->json('issue', []) as $issue) {
            $code = $issue['code'] ?? '';
            if (is_string($code) && preg_match('/^[a-z-]{1,50}$/D', $code)) {
                $issues[] = $code;
            }
        }
        $hint = $issues ? ' Kode validasi: '.implode(', ', array_unique($issues)).'.' : '';
        $result = $success ? 'BERHASIL TERKIRIM | ID: '.$id : strtoupper($status);
        $this->log($success ? 'success' : 'warning', $status, $no, "{$label} | {$result} | HTTP {$response->status()}.".$hint, $response->status());
        if ($success) {
            if ($type === 'Observation') {
                $value = data_get($payload, 'valueQuantity.value');
                $unit = data_get($payload, 'valueQuantity.unit', '');
                if ($value === null && isset($payload['component'])) {
                    $value = implode('/', array_column(array_column($payload['component'], 'valueQuantity'), 'value'));
                    $unit = 'mmHg';
                }
                $value ??= data_get($payload, 'valueCodeableConcept.text');
                if ($value !== null) {
                    $this->terminal('info', "{$label} | DATA TERKIRIM: {$value} {$unit}");
                }
            }
            $this->mirror($legacy, $id);
        }

        return $success ? $id : null;
    }

    private function record(string $key, string $no, string $type, string $status, ?string $id = null, ?int $httpCode = null): void
    {
        $now = now('Asia/Jakarta');
        DB::connection('clinic')->table('satu_sehat_service_resources')->upsert([
            ['resource_key' => $key, 'no_rawat' => $no, 'resource_type' => $type, 'resource_id' => $id,
                'status' => $status, 'http_code' => $httpCode, 'last_attempt_at' => $now, 'created_at' => $now, 'updated_at' => $now],
        ], ['resource_key'], ['resource_id', 'status', 'http_code', 'last_attempt_at', 'updated_at']);
    }

    private function resourceLabel(string $type, string $key): string
    {
        if ($type === 'Observation') {
            $name = explode(':', $key)[1] ?? '';
            $labels = ['suhu' => 'Suhu', 'respirasi' => 'Respirasi', 'nadi' => 'Nadi', 'spo2' => 'SpO2',
                'gcs' => 'GCS', 'tinggi' => 'Tinggi Badan', 'berat' => 'Berat Badan',
                'lingkarperut' => 'Lingkar Perut', 'tensi' => 'Tekanan Darah', 'kesadaran' => 'Kesadaran'];

            return 'Observation '.($labels[$name] ?? $name);
        }

        return $type;
    }

    private function terminal(string $level, string $message): void
    {
        // Status scan ulang hanya ke terminal, tidak menumpuk log database.
        ($this->output)($level, preg_replace('/[\r\n\x1b]/', ' ', $message));
    }

    private function mirror(array $legacy, string $id): void
    {
        [$table, $where, $idColumn, $extra, $status] = $legacy;
        if (! $this->hasTable($table)) {
            return;
        }
        $values = $extra + [$idColumn => $id, 'status' => $status];
        // Respons minimal untuk kompatibilitas tabel lama, tanpa menyalin data klinis ke log.
        $columns = Schema::connection('clinic')->getColumnListing($table);
        foreach (['response', 'response_message'] as $column) {
            if (in_array($column, $columns, true)) {
                $values[$column] = json_encode(['id' => $id]);
            }
        }
        foreach (['tanggal_kirim', 'updated_at'] as $column) {
            if (in_array($column, $columns, true)) {
                $values[$column] = now('Asia/Jakarta');
            }
        }
        $values = array_intersect_key($values, array_flip($columns));
        $query = DB::connection('clinic')->table($table)->where($where);
        $existing = $query->first();
        if ($existing && ($existing->$idColumn ?? null) === $id && strtolower($existing->status ?? '') === strtolower($status)) {
            return;
        }
        if ($existing) {
            $query->update($values);
        } else {
            if (in_array('created_at', $columns, true)) {
                $values['created_at'] = now('Asia/Jakarta');
            }
            DB::connection('clinic')->table($table)->insert($where + $values);
        }
    }

    private function hasTable(string $table): bool
    {
        return $this->tables[$table] ??= Schema::connection('clinic')->hasTable($table);
    }

    private function identifier(string $type, string $value): array
    {
        return ['system' => "http://sys-ids.kemkes.go.id/{$type}/{$this->organization}", 'use' => 'official', 'value' => $value];
    }

    private function concept(string $system, string $code, string $display): array
    {
        return ['coding' => [['system' => $system, 'code' => $code, 'display' => $display]]];
    }

    private function time(string $date, string $time): string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) || $date === '0000-00-00' || ! preg_match('/^\d{2}:\d{2}:\d{2}$/D', $time)) {
            throw new RuntimeException('Tanggal/jam sumber tidak valid.');
        }

        return CarbonImmutable::parse($date.' '.$time, 'Asia/Jakarta')->utc()->toIso8601String();
    }

    private function number(mixed $value): ?float
    {
        $value = str_replace(',', '.', trim((string) $value));

        return is_numeric($value) && is_finite((float) $value) ? (float) $value : null;
    }

    private function log(string $level, string $status, string $no, string $message, ?int $code = null): void
    {
        ServiceLog::create([ 'service_run_id' => $this->run->id,
            'service_name' => 'satu_sehat', 'level' => $level, 'status' => $status, 'reference' => $no,
            'message' => $message, 'response_code' => $code]);
        $this->terminal($level, $message);
    }
}
