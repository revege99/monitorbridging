# Satu proyek, satu klinik

Aktivasi instalasi dan pemilihan klinik dihapus. Semua request web, endpoint
Java, display, anjungan, PDF dan worker memakai satu profil serta satu koneksi
SIMRS aplikasi. Tidak ada lagi pembacaan `installation_settings`, session
`active_clinic_id`, atau clinic_id pengguna untuk memilih database.

Konfigurasi baru pada database central lokal:

| Tabel | Isi |
| --- | --- |
| application_profiles | Satu profil klinik aplikasi |
| application_database_configurations | Satu koneksi SIMRS |
| application_bpjs_configurations | Satu konfigurasi BPJS |

Ketiganya memiliki primary key singleton `id = 1`, tanpa kolom `clinic_id`.
`config_pcare` dan `ss_config` tetap berada di SIMRS. Base URL tetap tersimpan
di central aplikasi. Role superadmin/admin dan token integrasi Java tetap berlaku.
User admin tidak perlu ditautkan ke clinic_id.

## Upgrade server

1. Hentikan BAT/worker lama dan lakukan upgrade saat layanan tidak dipakai.
2. Cadangkan database central dan file konfigurasi aplikasi.
3. Salin seluruh perubahan kode, bukan hanya BAT atau satu service.
4. Jalankan dari folder proyek server:

```shell
php artisan migrate --path=database/migrations/2026_11_12_000001_use_single_clinic_application_configuration.php --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Migrasi memilih sumber dari instalasi lama yang aktif. Bila hanya ada satu profil
dan belum ada aktivasi, profil itu digunakan. Bila ada beberapa profil tanpa
pilihan aktif yang jelas, migrasi berhenti sebelum membuat konfigurasi baru.
Tentukan satu sumber melalui versi lama sebelum upgrade; migrasi tidak menebak
profil berdasarkan urutan nama atau ID.

Profil, koneksi SIMRS dan BPJS disalin tanpa menghapus tabel lama. Password dan
secret yang sudah terenkripsi disalin sebagai ciphertext, tidak dienkripsi dua
kali. Tabel `clinics`, `clinic_databases`, `bpjs_configurations`,
`satu_sehat_configurations` central lama dan `installation_settings` hanya arsip;
kode baru tidak menggunakannya untuk memilih profil/koneksi. Data `ss_config`
dan data klinis SIMRS tidak disentuh migrasi ini.

Kolom clinic_id pada riwayat log/run/display lama dibiarkan nullable untuk
mempertahankan riwayat. Penulisan baru tidak mengisi atau memfilter clinic_id.
Migrasi tidak menyediakan rollback otomatis yang membuang konfigurasi baru.

5. Buka Setup Klinik untuk memeriksa Profil Klinik dan Konfigurasi DB; periksa
   Konfigurasi BPJS, PCare, Satu Sehat dan Base URL.
6. Jika SIMRS ada pada server yang sama, gunakan host `127.0.0.1` pada
   Konfigurasi DB. Migrasi mempertahankan host lama, termasuk IP ZeroTier,
   agar tidak diam-diam mengubah database tujuan. Pemilihan host lokal/LAN
   harus sesuai lokasi database yang sebenarnya.
7. Jalankan `php artisan service:satu-sehat --check`, lalu buka kembali BAT.
   Uji endpoint Java di server masing-masing menggunakan token instalasi itu.

URL dan body Java tidak berubah: batal menerima no_rawat/alasan; hadir dan
tidak hadir memakai endpoint yang sudah ada. Semua mengakses SIMRS tunggal yang
disimpan pada Konfigurasi DB. Koneksi tidak lagi di-purge/dibuka ulang oleh
resolver bila konfigurasinya sama; PDO dibuka ketika query pertama dijalankan.
Token anjungan lama perlu diverifikasi ulang karena sekarang terikat fingerprint
host/port/database, sehingga perubahan koneksi tidak memakai sesi pasien lama.

Instalasi baru dapat menjalankan `php artisan app:install` dan mengisi profil
serta konfigurasi tunggal. Tidak ada langkah Aktivasi Instalasi.
