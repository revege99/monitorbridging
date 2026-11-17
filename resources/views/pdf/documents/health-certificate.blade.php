@extends('pdf.layouts.document')

@section('title', 'Surat Keterangan Sehat')

@section('styles')
    @page { margin: 15mm; }
    body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.45; }
    .letterhead { border-bottom: 2pt solid #111; padding: 1mm 0 6mm; }
    .logo { width: 24mm; text-align: left; }
    .facility { text-align: center; }
    .legal { font-size: 14pt; font-weight: bold; line-height: 1.15; }
    .facility-name { font-size: 13pt; font-weight: bold; line-height: 1.15; }
    .facility-detail { font-size: 8.5pt; line-height: 1.3; }
    .title { margin-top: 12mm; text-align: center; font-size: 13pt; font-weight: bold; text-decoration: underline; }
    .number { text-align: center; }
    .body-copy { margin-top: 11mm; text-align: justify; }
    .data { margin-top: 7mm; }
    .data td { padding: 0.4mm 0; }
    .label { width: 34%; }
    .colon { width: 4%; text-align: center; }
    .value { width: 62%; }
    .section { margin-top: 7mm; }
    .purpose { margin-top: 7mm; }
    .signature { width: 42%; margin: 11mm 0 0 auto; text-align: center; }
    .sign-space { height: 26mm; }
@endsection

@section('content')
    @php($logoFile = $logo_path ? storage_path('app/public/'.$logo_path) : null)
    <div class="letterhead">
        <table><tr>
            <td class="logo">@if($logoFile && is_file($logoFile))<img src="data:{{ mime_content_type($logoFile) }};base64,{{ base64_encode(file_get_contents($logoFile)) }}" style="width:22mm;height:auto;">@endif</td>
            <td class="facility">
                <div class="legal">{{ $legal_name }}</div>
                <div class="facility-name">{{ $facility_name }}</div>
                <div class="facility-detail">{{ $facility_address }}</div>
                @if($facility_contact !== '')<div class="facility-detail">{{ $facility_contact }}</div>@endif
            </td>
            <td style="width:24mm"></td>
        </tr></table>
    </div>

    <div class="title">SURAT KETERANGAN SEHAT</div>
    <div class="number">Nomor: {{ $letter_number }}</div>

    <div class="body-copy">Yang bertandatangan di bawah ini, <strong>{{ $doctor }}</strong> dengan mengingat sumpah waktu menerima jabatan bahwa:</div>
    <table class="data">
        <tr><td class="label">Nama</td><td class="colon">:</td><td class="value"><strong>{{ $patient_name }}</strong></td></tr>
        <tr><td>Tempat Lahir/Tanggal Lahir</td><td>:</td><td>{{ $birth }}</td></tr>
        <tr><td>Jenis Kelamin</td><td>:</td><td>{{ $sex }}</td></tr>
        <tr><td>Alamat</td><td>:</td><td>{{ $address }}</td></tr>
    </table>

    <div class="section">Hasil Pemeriksaan sebagai berikut:</div>
    <table class="data">
        <tr><td class="label">Tinggi badan</td><td class="colon">:</td><td>{{ $height }}</td></tr>
        <tr><td>Berat badan</td><td>:</td><td>{{ $weight }}</td></tr>
        <tr><td>Tekanan darah</td><td>:</td><td>{{ $blood_pressure }}</td></tr>
        <tr><td>Temperatur</td><td>:</td><td>{{ $temperature }}</td></tr>
        <tr><td>Buta warna</td><td>:</td><td>{{ $color_blindness }}</td></tr>
    </table>

    <div class="purpose">Keperluan: <strong>{{ $purpose }}</strong></div>
    <div class="section">Menurut pemeriksaan kami, yang bersangkutan atas nama diatas benar berbadan <strong>{{ strtoupper($conclusion) }}</strong>.</div>
    <div class="section">Demikian Surat Keterangan ini diperbuat dengan sebenarnya untuk dapat dipergunakan seperlunya.</div>

    <div class="signature">
        <div>{{ $letter_city }}, {{ $letter_date }}</div>
        <div>Dokter Pemeriksa</div>
        <div class="sign-space"></div>
        <strong>{{ $doctor }}</strong>
    </div>
@endsection
