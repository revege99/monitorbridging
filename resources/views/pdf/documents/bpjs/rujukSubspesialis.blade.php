@extends('pdf.layouts.document')

@section('title', 'Surat Rujukan FKTP')

@section('styles')
    @page { margin: 9mm 12mm 8mm; }
    body { font-family: 'Times New Roman', Times, serif; font-size: 8.5pt; line-height: 1.17; }
    .sheet { min-height: 268mm; border: 0.7pt solid #111; box-sizing: border-box; padding: 6mm 5mm 5mm; }
    .top-logo { width: 46%; padding-top: 1mm; }
    .regional { width: 54%; padding-top: 1mm; font-size: 8.5pt; font-weight: bold; }
    .regional-label { display: inline-block; width: 27mm; }
    .title { text-align: center; font-size: 12pt; font-weight: bold; margin: 7mm 0 1.5mm; }
    .box { border: 0.6pt solid #111; }
    .box td { padding: 1mm 1.5mm; }
    .box-label { width: 32%; }
    .colon { width: 3%; text-align: center; }
    .recipient { margin-top: 4mm; }
    .patient { margin-top: 5mm; }
    .patient td { padding: 0 0 1.4mm; }
    .closing { margin-top: 2.5mm; }
    .schedule-note { font-size: 8pt; padding-top: 1.5mm; }
    .return { margin-top: 7mm; page-break-inside: avoid; }
    .return hr { border: 0; border-top: 0.7pt solid #111; margin: 0 0 2mm; }
    .section-title { text-align: center; font-size: 11pt; font-weight: bold; margin: 2mm 0 2.5mm; }
    .small { font-size: 8pt; }
    .check { display: inline-block; width: 3.5mm; height: 3.5mm; border: 0.7pt solid #111; vertical-align: middle; }
    .signature { height: 21mm; }
@endsection

@section('content')
    <div class="sheet">
        <table>
            <tr>
                <td class="top-logo">@include('pdf.partials.bpjs-logo', ['width' => '53mm'])</td>
                <td class="regional">
                    <span class="regional-label">Divisi Regional</span>: {{ $regional }}<br>
                    <span class="regional-label">Kantor Cabang</span>: {{ $branch_office }}
                </td>
            </tr>
        </table>

        <div class="title">Surat Rujukan FKTP</div>
        <table class="box">
            <tr><td class="box-label">No. Rujukan</td><td class="colon">:</td><td>{{ $referral_number }}</td></tr>
            <tr><td>Puskesmas / Dokter Keluarga</td><td class="colon">:</td><td>{{ $facility }}</td></tr>
            <tr><td>Kabupaten / Kota</td><td class="colon">:</td><td>{{ $district }}</td></tr>
        </table>

        <table class="recipient">
            <tr><td style="width: 25%">Kepada Yth. TS Dokter</td><td style="width: 3%">:</td><td>{{ $subspecialty }}</td></tr>
            <tr><td>Di</td><td>:</td><td>{{ $destination }}</td></tr>
        </table>
        <p>Mohon pemeriksaan dan penanganan lebih lanjut pasien :</p>

        <table class="patient">
            <tr><td style="width: 15%">Nama</td><td class="colon">:</td><td style="width: 36%">{{ $patient_name }}</td><td style="width: 7%">Umur</td><td class="colon">:</td><td style="width: 11%">{{ $age }}</td><td>{{ $birth_date }}</td></tr>
            <tr><td>No. Kartu BPJS</td><td>:</td><td>{{ $card_number }}</td><td>Status</td><td>:</td><td colspan="2">1 Utama/Tanggungan &nbsp; {{ $sex }} (L/P)</td></tr>
            <tr><td>Diagnosa</td><td>:</td><td colspan="5">{{ $diagnosis }}</td></tr>
            <tr><td>Telah diberikan</td><td>:</td><td colspan="5">&nbsp;</td></tr>
            @if ($has_tacc_reason)
                <tr><td>Alasan TACC</td><td>:</td><td colspan="5">{{ $tacc_reason }}</td></tr>
            @endif
        </table>

        <table class="closing">
            <tr><td colspan="2">Atas bantuannya, diucapkan terima kasih</td><td style="text-align: center">Salam Sejawat, {{ $printed_at }}</td></tr>
            <tr><td style="width: 30%; padding-top: 4mm">Tanggal Rencana Berkunjung</td><td style="width: 36%; padding-top: 4mm">&nbsp;</td><td rowspan="3" style="text-align: center; padding-top: 17mm">{{ $doctor }}</td></tr>
            <tr><td>Jadwal Praktek</td><td>{{ $schedule }}</td></tr>
            <tr><td colspan="2" class="schedule-note">Surat Rujukan Berlaku 1 (satu) kali kunjungan, berlaku sampai dengan {{ $valid_until }}</td></tr>
        </table>

        <div class="return">
            <hr>
            <div class="section-title">SURAT RUJUKAN BALIK</div>
            <p class="small">Teman Sejawat Yth.</p>
            <p class="small">Mohon kontrol selanjutnya penderita :</p>
            <table class="small">
                <tr><td style="width: 20%">Nama</td><td style="width: 3%">:</td><td>{{ $patient_name }}</td></tr>
                <tr><td>Diagnosa</td><td>:</td><td>&nbsp;</td></tr>
                <tr><td>Therapi</td><td>:</td><td>&nbsp;</td></tr>
            </table>
            <p class="small">Tindak lanjut yang dianjurkan :</p>
            <table class="small">
                <tr><td style="width: 6%"><span class="check"></span></td><td style="width: 47%">Pengobatan dengan Obat - Obatan<br>............................................................</td><td style="width: 6%"><span class="check"></span></td><td>Perlu Rawat Inap</td></tr>
                <tr><td><span class="check"></span></td><td>Kontrol Kembali ke RS tanggal : ........................</td><td><span class="check"></span></td><td>Konsultasi</td></tr>
                <tr><td><span class="check"></span></td><td>Lain-lain : ...................................................</td><td></td><td></td></tr>
                <tr><td colspan="2"></td><td colspan="2" style="text-align: center; padding-top: 4mm">Dokter RS</td></tr>
                <tr><td colspan="2"></td><td colspan="2" class="signature"></td></tr>
                <tr><td colspan="2"></td><td colspan="2" style="text-align: center">(.............................................)</td></tr>
            </table>
        </div>
    </div>
@endsection
