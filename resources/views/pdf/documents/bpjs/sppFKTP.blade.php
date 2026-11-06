@extends('pdf.layouts.document')

@section('title', 'Surat Pernyataan Pelayanan di FKTP')

@section('styles')
    .letterhead { position: relative; border-bottom: 0.7pt solid #333; padding-bottom: 3mm; }
    .letterhead-logo { position: absolute; left: 0; top: 0; }
    .heading { text-align: center; font-weight: bold; line-height: 1.6; }
    .visit { text-align: right; font-weight: bold; margin: 1.5mm 0 4mm; }
    .identity td { padding: 0 0 2mm; font-size: 8.5pt; }
    .identity .label { width: 35mm; }
    .identity .colon { width: 3mm; }
    .identity .value { width: 67mm; padding-right: 3mm; }
    .identity .right-label { width: 33mm; }
    .services { border-top: 0.7pt solid #333; border-bottom: 0.7pt solid #333; margin-top: 1mm; padding: 2mm 0; }
    .services-title { margin-bottom: 1.3mm; }
    .service-number { width: 6mm; text-align: center; }
    .service-list td { padding-bottom: 1mm; }
    .declaration { margin-top: 1.5mm; font-size: 8.5pt; text-align: justify; }
    .signature { margin-top: 7mm; margin-left: 58%; width: 42%; font-size: 8.5pt; page-break-inside: avoid; }
    .sign-space { height: 18mm; }
    .contact { margin-top: 4mm; }
@endsection

@section('content')
    <div class="keep-together">
        <div class="letterhead">
            <div class="letterhead-logo">@include('pdf.partials.bpjs-logo', ['width' => '45mm'])</div>
            <div class="heading">
                Surat Pernyataan Pelayanan di FKTP<br>
                {{ $facility }}
            </div>
        </div>
        <div class="visit">No Kunjungan: {{ $visit_number }}</div>

        <table class="identity">
            <tr>
                <td class="label">Nomor Rekam Medis</td><td class="colon">:</td><td class="value">{{ $medical_record }}</td>
                <td class="right-label">Tanggal Lahir</td><td class="colon">:</td><td>{{ $birth_date }}</td>
            </tr>
            <tr>
                <td>Nomor Kartu Peserta</td><td>:</td><td>{{ $card_number }}</td>
                <td>Umur</td><td>:</td><td>{{ $age }}</td>
            </tr>
            <tr>
                <td>NIK</td><td>:</td><td>{{ $nik }}</td>
                <td>Tanggal Pelayanan</td><td>:</td><td>{{ $service_date }}</td>
            </tr>
            <tr>
                <td>Nama</td><td>:</td><td>{{ $patient_name }}</td>
                <td>Jenis Pelayanan</td><td>:</td><td>{{ $service_type }}</td>
            </tr>
            <tr>
                <td>Jenis Kelamin</td><td>:</td><td>{{ $sex }}</td>
                <td>Alamat</td><td>:</td><td>{{ $address }}</td>
            </tr>
            <tr>
                <td>Nomor HP</td><td>:</td><td>{{ $phone }}</td>
                <td></td><td></td><td></td>
            </tr>
        </table>
    </div>

    <div class="services">
        <div class="services-title">Pelayanan:</div>
        <table class="service-list">
            @forelse ($services as $service)
                <tr><td class="service-number">{{ $loop->iteration }}</td><td>{{ $service }}</td></tr>
            @empty
                <tr><td>Belum ada tindakan PCare yang tercatat.</td></tr>
            @endforelse
        </table>
    </div>

    <div class="keep-together">
        <div class="declaration">
            Pasien / Keluarga menyatakan bahwa benar, pasien telah mendapatkan pelayanan tanpa dikenakan iur biaya
            serta memberikan persetujuan kepada BPJS Kesehatan untuk menggunakan informasi medis yang tertera
            di status kesehatan pasien sebagai salah satu syarat pengajuan klaim pelayanan program JKN.
        </div>
        <div class="signature">
            <div>Pasien / Keluarga</div>
            <div class="sign-space"></div>
            <div>{{ $patient_name }}</div>
            <div class="contact">No telp yang dapat dihubungi: {{ $phone === '-' ? '' : $phone }}</div>
        </div>
    </div>
@endsection
