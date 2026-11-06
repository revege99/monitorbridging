# PDF Khanza

Semua PDF Khanza ditempatkan di struktur ini dan dikelompokkan per modul.
Kerangka awal sudah sesuai Laravel; pengelompokan modul, route khusus, validasi,
dan autentikasi bersama ditambahkan agar tetap rapi ketika dokumen bertambah.

## Struktur

```text
routes/
  api.php                              # Memasang grup integrasi PDF
  pdf.php                              # Semua route dokumen PDF
app/Http/
  Controllers/Api/Pdf/Bpjs/            # Controller per dokumen BPJS
  Requests/Api/Pdf/PrintPdfRequest.php  # Validasi no_rawat bersama
  Middleware/AuthenticatePdfIntegration.php
app/Services/Pdf/
  Bpjs/                               # Pengambilan dan penyusunan data BPJS
  PdfRenderer.php                     # Renderer bersama Dompdf
config/pdf.php                        # Ukuran/orientasi per dokumen
public/assets/pdf/logos/               # Logo bersama, tanpa data pasien
resources/views/pdf/
  layouts/                            # Layout, margin, gaya halaman
  partials/                           # Kop, identitas pasien, tanda tangan
  documents/bpjs/                      # Template per dokumen BPJS
tests/Feature/Api/Pdf/
  PdfIntegrationTest.php               # Kontrak autentikasi dan input
  Bpjs/                              # Test dokumen BPJS
```

Dokumen pertama: **sppFKTP — Surat Pernyataan Pelayanan di FKTP**.

- Controller: `app/Http/Controllers/Api/Pdf/Bpjs/SppFktpController.php`
- Service: `app/Services/Pdf/Bpjs/SppFktpService.php`
- Template: `resources/views/pdf/documents/bpjs/sppFKTP.blade.php`
- Test: `tests/Feature/Api/Pdf/Bpjs/SppFktpTest.php`

Endpoint aktif: `GET|POST /api/integration/pdf/bpjs/sppFKTP`.
POST mengirim `no_rawat` melalui body; GET dapat mengirimnya melalui query string.
GET dapat dibuka langsung di browser tanpa token, login, tanda tangan URL, atau
batas waktu. POST tetap memerlukan header `X-Integration-Token`. Siapa pun yang
mengetahui URL dan no_rawat dapat mengakses PDF melalui GET.
Ukuran awal A5 landscape dengan margin atas/bawah 10 mm dan kiri/kanan 12 mm.
Ubah ukuran/orientasi melalui `config/pdf.php` bila printer memakai kertas lain.
Daftar panjang dapat berlanjut ke halaman berikutnya; area tanda tangan dijaga
agar tidak terpisah. Tidak ada tanda tangan elektronik atau tanda tangan otomatis.

## Sumber data surat

Seluruh query memakai koneksi **clinic** dari instalasi aktif, tanpa memanggil
API BPJS. `ActiveInstallation::resolveForPdf()` hanya mensyaratkan klinik aktif
dan konfigurasi database; konfigurasi credential/base URL BPJS tidak diperlukan.

| Isi | Sumber |
| --- | --- |
| Kode dan nama fasilitas | `setting.kode_ppk`, `setting.nama_instansi`; utamakan baris `aktifkan = Yes`, kemudian urut nama |
| Kunjungan dan nomor rekam medis | `reg_periksa`, dipilih persis berdasarkan `no_rawat` |
| Nama, NIK, jenis kelamin, lahir, alamat, telepon | `pasien`, melalui `reg_periksa.no_rkm_medis` |
| Nomor kartu | `pcare_pendaftaran.noKartu`, cadangan `pasien.no_peserta` |
| Tanggal pelayanan | `pcare_pendaftaran.tglDaftar`, cadangan `reg_periksa.tgl_registrasi` |
| Jenis pelayanan | `pcare_pendaftaran.kdTkp`: 10 = RJTP, 20 = RITP, 50 = Promotif Preventif; cadangan `reg_periksa.status_lanjut` |
| Nomor kunjungan | `pcare_kunjungan_umum.noKunjungan`, dicocokkan persis melalui `no_rawat` |
| Pelayanan | `pcare_tindakan_ralan_diberikan` dan `jns_perawatan.nm_perawatan`, satu baris per kode tindakan |
| Umur | Selisih tanggal lahir dan tanggal pelayanan, bukan umur pada hari cetak |

Nomor kunjungan yang belum tersedia, kosong, atau bernilai `0` ditampilkan `-`.
Nomor ini tetap tersedia meskipun belum ada tindakan. Nomor kunjungan dari tabel
tindakan tidak digunakan sebagai pengganti.
Tindakan kosong ditampilkan sebagai keterangan belum ada tindakan tercatat;
tindakan seperti ANC tidak ditambahkan otomatis. Field opsional kosong memakai `-`.
Identitas fasilitas yang belum tersedia menghasilkan 503, bukan kop fasilitas contoh.

Logo bersama berada di `public/assets/pdf/logos/bpjs-kesehatan.png`, digunakan
melalui partial `pdf.partials.bpjs-logo`. Logo disematkan langsung sehingga tidak
memerlukan akses internet. `setting.logo` tidak menggantikan logo BPJS tersebut.

Untuk modul baru, buat folder sejajar saat diperlukan, misalnya `Farmasi` di PHP
serta `farmasi` di Blade dan URL. Jangan membuat folder per pasien/no_rawat.

## Membuka sppFKTP dari Khanza

Endpoint browser: **GET** `/api/integration/pdf/bpjs/sppFKTP?no_rawat=...`.
Kirim no_rawat sebagai query yang di-URL-encode. Tidak ada token, login, signature,
atau waktu kedaluwarsa untuk GET. Setiap permintaan membuat PDF dari data terkini.
Respons memakai `application/pdf`, `Content-Disposition: inline`, dan
`Cache-Control: private, no-store`. Browser menampilkan PDF apabila viewer PDF
bawaannya aktif; pengaturan browser pengguna dapat memilih mengunduhnya.

Contoh aksi tombol cetak Khanza:

```java
import java.awt.Desktop;
import java.net.URL;
import java.net.URLEncoder;

// noRawat berasal dari kunjungan yang dipilih di Khanza.
URL obj = new URL("http://" + koneksiDB.HOSTCALL() + "/"
    + koneksiDB.FOLDERCALL()
    + "/public/api/integration/pdf/bpjs/sppFKTP?no_rawat="
    + URLEncoder.encode(noRawat, "UTF-8"));
Desktop.getDesktop().browse(obj.toURI());
```

Ini membuka alamat web menggunakan browser bawaan, tanpa menyimpan file
sementara melalui Java. Letakkan import di awal file dan pemanggilan dalam
method tombol/menu; tangani exception melalui penanganan error form Khanza.

Helper opsional tersedia di [SppFktpClient.java](examples/khanza/SppFktpClient.java).
Salin ke struktur berikut dalam proyek Java Khanza:

```text
src/bridging/pdf/SppFktpClient.java
```

Pemanggilan helper dengan URL endpoint tanpa query:

```java
SppFktpClient.openInBrowser(obj, noRawat);
```

`openInBrowser` mendukung Java 8+ tanpa library tambahan. Method lama `download`
tetap tersedia untuk integrasi POST yang membutuhkan file lokal dan token header.
POST tidak diubah menjadi akses tanpa autentikasi.

Nama route GET: `api.integration.pdf.bpjs.sppFKTP`.
Nama route POST: `api.integration.pdf.bpjs.sppFKTP.post`.
Nama file respons: `sppFKTP-{no_rawat-dengan-strip}.pdf`.
Huruf besar/kecil pada path URL harus mengikuti `sppFKTP`.

Kontrak error: 422 input tidak valid, 404 kunjungan tidak ditemukan,
429 pembatasan permintaan, 503 konfigurasi belum siap, 500 kegagalan cetak.
POST juga dapat menghasilkan 401 jika header token kosong/salah. Token POST
mengikuti `KHANZA_INTEGRATION_TOKEN` dan tidak diletakkan dalam query URL.

Bagian `/public` mengikuti deployment Khanza pada contoh; hilangkan jika document
root server sudah menunjuk ke folder `public` Laravel. Tidak ada endpoint pembuat
tautan sementara untuk alur browser ini.

## Menambahkan dokumen

1. Tentukan modul, jenis dokumen, sumber data berdasarkan no_rawat, ukuran kertas,
   orientasi, kop, font, serta tanda tangan.
2. Buat service data per dokumen dengan koneksi database klinik yang benar dan
   query berparameter. Pastikan kunjungan tersedia. Jangan query dari Blade.
   Gunakan `ActiveInstallation::resolveForPdf()` dan `configureClinicDatabase()`
   untuk memilih koneksi instalasi aktif tanpa ketergantungan API BPJS.
3. Buat controller invokable dengan `PrintPdfRequest` atau turunannya, panggil
   service, lalu gunakan `PdfRenderer` untuk merender template dan respons PDF.
4. Tambahkan route eksplisit di `routes/pdf.php`, misalnya:

   ```php
   use App\Http\Controllers\Api\Pdf\RekamMedis\ResumeMedisController;

   Route::prefix('rekam-medis')->name('rekam-medis.')->group(function () {
       Route::post('resume-medis', ResumeMedisController::class)->name('resume-medis');
   });
   ```

   Prefix `/api/integration/pdf`, nama `api.integration.pdf.`, autentikasi, dan
   throttle sudah dipasang di `routes/api.php`. Jangan menambahkan `/api` lagi.
   Jangan menerima nama template, nama class, SQL, atau path file dari pemanggil.
5. Tambahkan test otorisasi, input, kunjungan tidak ditemukan, kecocokan data,
   header dan isi PDF. Verifikasi hasil cetak secara visual.

Dompdf dipasang melalui `barryvdh/laravel-dompdf`; jalankan `composer install`
saat deployment. Akses jaringan, eksekusi PHP, dan JavaScript renderer dimatikan.
PDF dikirim langsung tanpa disimpan di folder publik. Pengujian menggunakan
SQLite in-memory dan data fiktif, bukan database pasien produksi.

```shell
php artisan test --filter=Pdf
php artisan route:list --path=integration/pdf
```
