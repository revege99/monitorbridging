# Pengaturan nomor rekam medis Anjungan

Menu **Konfigurasi > Anjungan** tersedia untuk superadmin. Pengaturan disimpan
di tabel `kiosk_configurations` pada database central aplikasi, satu baris ID 1,
tanpa clinic_id. Data pasien tetap disimpan pada tabel `pasien` di SIMRS.

Pengaturan meliputi jumlah digit angka (1–12), gunakan pemisah atau tidak,
karakter pemisah (`-`, `.`, `/`), dan pengelompokan digit, misalnya `2-2-2` atau
`3-3`. Jumlah pengelompokan harus sama dengan jumlah digit. Panjang total nomor
terformat dibatasi 15 karakter, mengikuti kolom no_rkm_medis SIMRS.

Default sebelum ada pengaturan tersimpan tetap 6 digit dengan format `00-00-01`.
Jika pemisah dimatikan hasilnya `000001`. Contoh pada halaman adalah nomor 1,
bukan hasil alokasi nomor berikutnya. Format UI Anjungan (pattern, panjang,
placeholder) dan validasi server mengikuti pengaturan yang sama. Setelah
mengubah pengaturan, muat ulang Anjungan atau verifikasi ulang peserta.

Generator mencari angka terbesar di seluruh nomor RM numerik SIMRS, termasuk
format lama dengan tanda strip, titik atau garis miring. Contoh: nomor terakhir
`00-00-99` menghasilkan `000100` saat pemisah dimatikan, bukan memulai dari 1.
Nomor dengan prefix huruf tidak digunakan sebagai dasar urutan numerik.
Nol di depan bukan prefix tetap. Untuk 8 digit: `00025973` dilanjutkan ke
`00025974`, `00099999` ke `00100000`, dan `01712214` ke `01712215`.
Jika `01712214` ada, nomor itu menjadi acuan meskipun ada pasien dengan nomor
`00025973` yang dimasukkan lebih baru. Generator tidak membaca maupun mengubah
`set_no_rkm_medis`.
Jika angka berikutnya melampaui kapasitas digit, aplikasi menolak pembuatan dan
meminta penambahan digit; tidak memotong angka atau memulai ulang dari nol.

Nomor yang sudah dimiliki pasien tidak diubah. Pemeriksaan bentrok memakai nilai
numerik yang dinormalisasi, sehingga `00-00-01` dan `000001` dianggap nomor yang
sama untuk pembuatan pasien baru. Nomor usulan tetap dapat diedit sebelum Simpan
dan bukan reservasi. Pemeriksaan akhir diulang saat penyimpanan. Pendaftaran
pasien baru dari aplikasi ini memakai cache lock per koneksi SIMRS, lalu transaksi
database. Penulisan langsung dari aplikasi SIMRS lain tetap harus mengikuti
aturan nomor/keunikan yang disepakati bersama.

## Deployment

Salin seluruh perubahan kode, lalu jalankan dari folder proyek:

```shell
php artisan migrate --path=database/migrations/2026_11_12_000002_create_kiosk_configurations_table.php --force
php artisan route:clear
php artisan view:clear
```

Migrasi hanya menambah tabel pengaturan pada central, tidak mengubah nomor RM
atau tabel pasien SIMRS. Setelah itu buka Konfigurasi > Anjungan dan simpan
format yang diinginkan. Tidak perlu mengubah BAT service BPJS/Satu Sehat.
