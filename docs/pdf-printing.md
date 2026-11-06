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
Keduanya tetap memerlukan header `X-Integration-Token`. URL yang dibuka langsung
di address bar browser tidak mengirim header tersebut sehingga menghasilkan 401.
Client Java di bawah mengirim header dan menerima PDF tanpa perlu login browser.
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

## Kontrak URL Khanza

- Metode standar: **POST** `/api/integration/pdf/{modul}/{dokumen}`.
- Body JSON atau form: `no_rawat` wajib berupa string, maksimum 17 karakter,
  mengikuti batas pada integrasi proyek saat ini. Aturan tambahan khusus dokumen
  dapat menggunakan request turunan.
- Jangan memakai no_rawat sebagai segmen path karena dapat mengandung `/`,
  misalnya `2026/11/06/000001`.
- Header autentikasi: `X-Integration-Token`, memakai konfigurasi yang sudah ada,
  `services.khanza.integration_token` dari `KHANZA_INTEGRATION_TOKEN`.
  Token kosong/salah ditolak 401. Jangan kirim token melalui query URL.
- Respons sukses harus `application/pdf` dengan `Content-Disposition: inline`
  dan nama berkas aman. Middleware memasang `Cache-Control: private, no-store`.
- Kontrak error: 401 autentikasi, 422 input, 404 kunjungan tidak ditemukan,
  429 pembatasan permintaan, 503 konfigurasi belum siap, 500 kegagalan cetak.
  Pesan error tidak membocorkan SQL/kredensial.

Contoh Java untuk surat pernyataan pelayanan:

```java
import bridging.pdf.SppFktpClient;
import java.awt.Desktop;
import java.net.URL;
import java.nio.file.Path;

// noRawat diambil dari kunjungan yang dipilih di Khanza.
// Token pada komputer Khanza harus sama dengan KHANZA_INTEGRATION_TOKEN di web.
String integrationToken = System.getenv("KHANZA_INTEGRATION_TOKEN");
URL obj = new URL("http://" + koneksiDB.HOSTCALL() + "/"
    + koneksiDB.FOLDERCALL()
    + "/public/api/integration/pdf/bpjs/sppFKTP");
Path pdf = SppFktpClient.download(obj, noRawat, integrationToken);
Desktop.getDesktop().open(pdf.toFile());
```

### Struktur di proyek Java Khanza

```text
src/
  bridging/
    pdf/
      SppFktpClient.java             # HTTP POST, unduh dan validasi respons PDF
```

Salin [SppFktpClient.java](examples/khanza/SppFktpClient.java) yang disediakan di
repository web ini ke `src/bridging/pdf/SppFktpClient.java` pada proyek Khanza.
Package-nya `bridging.pdf`; mendukung Java 8+ tanpa dependency tambahan.
Tambahkan pemanggilan di atas pada aksi tombol/menu cetak sppFKTP di form Khanza.
Bagian `import` diletakkan di awal file, sedangkan pemanggilan di dalam method.
Jalankan unduhan di background worker (misalnya `SwingWorker`) agar UI tidak
membeku; tangani `IOException` untuk menampilkan pesan kegagalan kepada operator.

Client mengirim `no_rawat` sebagai form POST dan token melalui header, memeriksa
status HTTP, Content-Type, serta penanda `%PDF-`, kemudian mengembalikan file
sementara dengan awalan `sppFKTP-`. Redirect tidak diikuti agar token tidak
diteruskan ke host lain. File parsial dihapus jika unduhan gagal; file berhasil
ditandai untuk dihapus saat JVM keluar dan dapat dihapus lebih awal setelah
viewer ditutup. Jangan menaruh token dalam source code atau query URL.

Nama route Laravel: `api.integration.pdf.bpjs.sppFKTP`.
Nama file respons: `sppFKTP-{no_rawat-dengan-strip}.pdf`.
Huruf besar/kecil pada path URL harus mengikuti `sppFKTP`.

Membuat `new URL(...)` saja belum mengirim POST, body, atau header. Khanza harus
membaca byte respons untuk membuka PDF. Gunakan HTTPS jika tersedia untuk
melindungi token dan data pasien. `/public` mengikuti deployment contoh Khanza;
hilangkan jika document root server sudah menunjuk ke folder `public` Laravel.

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
