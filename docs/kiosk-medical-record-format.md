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

Generator memakai `set_no_rkm_medis.no_rkm_medis` pada database SIMRS sebagai
pencatat nomor terakhir, lalu mengusulkan nilai + 1 sesuai format pengaturan.
Tabel harus sudah ada dan berisi tepat satu baris nomor numerik yang valid.
Aplikasi tidak membuat/reset tabel pencatat dan tidak memakai MAX seluruh pasien.

Contoh pencatat `00025973` menghasilkan usulan `00025974`. Bila pencatat masih
`00025970` tetapi nomor 71 sampai 73 sudah digunakan, generator melewatinya
sehingga usulan menjadi `00025974`. Nomor besar dari urutan lama seperti
`01712214` tidak membuat loncatan selama bukan nomor pada pencatat. Pemeriksaan
nomor terpakai dinormalisasi tanpa pemisah untuk menghindari bentrok lintas format.

Menampilkan usulan tidak mengubah pencatat. Saat pasien baru disimpan, baris
pencatat dikunci dalam transaksi database SIMRS, nomor dicek lagi, pasien diinsert,
lalu pencatat diset ke nomor pasien yang berhasil disimpan. Nilai pencatat disimpan
numerik tanpa pemisah; misalnya pasien `00-00-01` menghasilkan pencatat `000001`.
Pencatat tidak ditambah dua kali. Jika transaksi gagal, keduanya di-rollback.
Jika peserta ternyata sudah memiliki pasien, pencatat tidak dinaikkan.

Nol di depan bukan prefix tetap: `00099999` berlanjut ke `00100000` untuk format
8 digit. Jika kapasitas digit habis, generator menolak tanpa memotong angka.
Nomor pasien lama tidak diubah. Usulan bukan reservasi; jika nomor terakhir sudah
berubah oleh pendaftaran lain sebelum Simpan, pengguna diminta verifikasi ulang.
Anjungan juga memakai cache lock per koneksi SIMRS. Khanza/aplikasi lain harus
memakai pencatat dan aturan keunikan yang konsisten agar urutan tetap selaras.

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
