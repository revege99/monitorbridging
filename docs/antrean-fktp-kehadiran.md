# Pencatatan hadir dan tidak hadir

Jalankan `database/sql/antrean_fktp_attendances.sql` pada masing-masing database
SIMRS sebelum menggunakan pencatatan kehadiran. Koneksi memakai `clinic`, sama
seperti `config_pcare`, mengikuti instalasi klinik aktif.

Endpoint `POST /api/integration/antrean-fktp/panggil` mencatat `waktu_hadir`.
Endpoint `POST /api/integration/antrean-fktp/tidak-hadir` mencatat
`waktu_tidak_hadir`. Keduanya memerlukan header `X-Integration-Token` dan body
`no_rawat` sesuai endpoint yang sudah ada.

Hanya respons BPJS dengan `metadata.code = 200` yang memicu pencatatan.
Waktu adalah tanggal dan jam saat respons sukses diterima (Asia/Jakarta, UTC+7),
bukan waktu pemanggilan display atau tanggal kunjungan yang dikirim ke BPJS.
Satu no_rawat memiliki satu baris; masing-masing waktu hanya diisi sekali.
Kegagalan dan pemanggilan ulang tidak mengganti waktu yang sudah tersimpan.
Kolom hadir dan tidak hadir independen: jika kedua aksi pernah sukses,
kedua kolom dapat terisi tanpa saling menghapus.

Menu Khanza panggil, panggil next, dan panggil skip mendapat pencatatan yang sama
selama aksi hadir/tidak hadir menggunakan endpoint di atas. Endpoint display
`/api/integration/display/panggil` tidak mencatat kehadiran. Kode menu Java Khanza
tidak berada dalam repository ini.
