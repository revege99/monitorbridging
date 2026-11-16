@extends('pdf.layouts.document')

@section('title', 'Surat Rujukan FKTP')

@section('styles')
    @page { margin: 12mm; }
    body { font-size: 8.5pt; line-height: 1.18; }
    .bordered { border: 0.6pt solid #222; }
    .bordered td { padding: 1.2mm 1.5mm; }
    .logo { width: 55mm; }
    .regional { font-size: 8pt; }
    .title { text-align: center; font-size: 13pt; font-weight: bold; padding: 2mm 0 3mm; }
    .label { width: 31%; }
    .colon { width: 3%; text-align: center; }
    .value { width: 66%; }
    .section-title { text-align: center; font-weight: bold; font-size: 11pt; padding: 2mm; }
    .small { font-size: 8pt; }
    .checkbox { width: 6mm; font-size: 12pt; text-align: center; }
    .signature { height: 20mm; }
    .return { page-break-inside: avoid; }
@endsection

@section('content')
    <table>
        <tr>
            <td class="logo">@include('pdf.partials.bpjs-logo', ['width' => '48mm'])</td>
            <td class="regional">
                <strong>Devisi Regional</strong> : REGIONAL I-SUMATERA UTARA<br>
                <strong>Kantor Cabang</strong> : SIBOLGA
            </td>
        </tr>
    </table>

    <div class="title">Surat Rujukan FKTP</div>
    <table class="bordered">
        <tr><td class="label">No. Rujukan</td><td class="colon">:</td><td class="value">{{ $referral_number }}</td></tr>
        <tr><td>Puskesmas / Dokter Keluarga</td><td class="colon">:</td><td>{{ $facility }}</td></tr>
        <tr><td>Kabupaten / Kota</td><td class="colon">:</td><td>{{ $district }}</td></tr>
    </table>

    <table style="margin-top: 3mm">
        <tr><td style="width: 27%">Kepada Yth. TS Dokter</td><td style="width: 3%">:</td><td>{{ $subspecialty }}</td></tr>
        <tr><td>Di</td><td>:</td><td>{{ $destination }}</td></tr>
    </table>
    <p>Mohon pemeriksaan dan penanganan lebih lanjut pasien:</p>
    <table>
        <tr><td style="width: 12%">Nama</td><td style="width: 3%">:</td><td style="width: 33%">{{ $patient_name }}</td><td style="width: 8%">Umur</td><td style="width: 3%">:</td><td style="width: 16%">{{ $age }}</td><td style="width: 25%">{{ $birth_date }}</td></tr>
        <tr><td>No. Kartu BPJS</td><td>:</td><td>{{ $card_number }}</td><td>Status</td><td>:</td><td>1 Utama/Tanggungan</td><td>{{ $sex }} (L/P)</td></tr>
        <tr><td>Diagnosa</td><td>:</td><td colspan="5">{{ $diagnosis }}</td></tr>
        <tr><td>Telah diberikan</td><td>:</td><td colspan="5">&nbsp;</td></tr>
        <tr><td>Alasan TACC</td><td>:</td><td colspan="5">{{ $tacc_reason }}</td></tr>
    </table>

    <table style="margin-top: 4mm">
        <tr><td colspan="2">Atas bantuannya, diucapkan terima kasih.</td><td style="text-align: center">Salam Sejawat, {{ $printed_at }}</td></tr>
        <tr><td style="width: 30%">Tanggal Rencana Berkunjung</td><td style="width: 35%">&nbsp;</td><td rowspan="3" style="text-align: center">{{ $doctor }}</td></tr>
        <tr><td>Jadwal Praktek</td><td>{{ $schedule }}</td></tr>
        <tr><td colspan="2">Surat Rujukan Berlaku 1 (satu) kali kunjungan, berlaku sampai dengan {{ $valid_until }}</td></tr>
    </table>

    <div class="return">
        <hr>
        <div class="section-title">SURAT RUJUKAN BALIK</div>
        <p class="small">Teman Sejawat Yth.<br>Mohon kontrol selanjutnya penderita:</p>
        <table>
            <tr><td style="width: 20%">Nama</td><td style="width: 3%">:</td><td>{{ $patient_name }}</td></tr>
            <tr><td>Diagnosa</td><td>:</td><td>&nbsp;</td></tr>
            <tr><td>Terapi</td><td>:</td><td>&nbsp;</td></tr>
        </table>
        <p class="small">Tindak lanjut yang dianjurkan:</p>
        <table class="small">
            <tr><td class="checkbox">&#9633;</td><td style="width: 44%">Pengobatan dengan obat-obatan<br>............................................................</td><td class="checkbox">&#9633;</td><td>Perlu rawat inap</td></tr>
            <tr><td class="checkbox">&#9633;</td><td>Kontrol kembali ke RS tanggal: ........................</td><td class="checkbox">&#9633;</td><td>Konsultasi</td></tr>
            <tr><td class="checkbox">&#9633;</td><td>Lain-lain: ...................................................</td><td></td><td></td></tr>
            <tr><td colspan="2"></td><td colspan="2" style="text-align: center">Dokter RS</td></tr>
            <tr><td colspan="2"></td><td colspan="2" class="signature"></td></tr>
            <tr><td colspan="2"></td><td colspan="2" style="text-align: center">(.............................................)</td></tr>
        </table>
    </div>
@endsection
