# Service Satu Sehat

Worker Laravel: `service:satu-sehat`. Launcher Windows: `service-satu-sehat.bat`
di root proyek. Hasil pengiriman disimpan pada database SIMRS klinik instalasi
aktif; riwayat worker dan log ringkas disimpan di central (`service_name = satu_sehat`).
Halaman monitor service Antrean yang ada tetap khusus Antrean.

## Persiapan

1. Pastikan profil klinik aplikasi dan koneksi database SIMRS benar. Tidak ada aktivasi instalasi.
2. Isi Konfigurasi > Satu Sehat untuk klinik tersebut: Client ID, Client Secret,
   Organization ID, Location ID. Data disimpan di `ss_config` pada database
   SIMRS klinik terpilih, memakai koneksi database klinik yang terdaftar.
   Secret disimpan sesuai format tabel lama agar kompatibel dengan integrasi
   SIMRS yang sudah ada. Secret ditampilkan pada form Edit untuk superadmin,
   tetapi tidak di-flash ke sesi ketika validasi gagal.
3. Isi Base URL Satu Sehat: Auth URL endpoint token lengkap dan FHIR URL root
   layanan. Keduanya wajib HTTPS dan harus berada pada lingkungan yang sama.
   Tidak ada URL produksi hardcoded dalam worker.
4. Jalankan `database/sql/satu_sehat_service_resources.sql` di masing-masing
   database SIMRS. Jangan jalankan SQL ini hanya pada database central.
5. Hentikan worker lama di proyek lain sebelum worker baru mulai mengirim.
   File lock baru hanya melindungi proses dalam direktori proyek ini.

Semua konfigurasi koneksi yang digunakan skrip lama tersedia di menu yang ada.
Worker membaca `ss_config` dari koneksi `clinic` sesuai konfigurasi SIMRS tunggal aplikasi.
Jika tabel belum ada, jalankan `database/sql/ss_config.sql` pada setiap SIMRS.
Tabel harus memiliki `id`, `client_id`, `client_secret`, `organization_id`, dan
`location_id`. Satu konfigurasi per database klinik; jika tabel lama memiliki
lebih dari satu baris, worker memakai ID paling kecil. Kredensial tidak lagi
dibaca dari `central.satu_sehat_configurations`. Data central lama tidak dihapus
atau dipindahkan otomatis; isi/verifikasi konfigurasi di menu sesuai kliniknya.
Worker memakai resolver instalasi tanpa dependensi kredensial BPJS.
Upgrade versi multi-klinik mengikuti `docs/single-clinic-deployment.md`.

```shell
php artisan service:satu-sehat --check
php artisan service:satu-sehat --once
php artisan service:satu-sehat
```

`--check` hanya memeriksa instalasi, kelengkapan/format konfigurasi, koneksi
database, dan keberadaan tabel kontrol. Perintah itu tidak memanggil API atau
memvalidasi seluruh skema/mapping/terminologi. `--once` benar-benar mengirim data.
Setelah konfigurasi siap, jalankan BAT dengan klik dua kali untuk worker berulang.
PHP harus versi yang didukung proyek (minimal 8.3) dan memiliki PDO MySQL.
BAT menggunakan PHP_BIN override, Laragon, PATH, lalu XAMPP seperti launcher Antrean.

Default scan tanggal registrasi kemarin sampai hari ini WIB, diperbarui otomatis ketika berganti hari, interval
30 detik. Backfill harus eksplisit, misalnya:

```shell
php artisan service:satu-sehat --once --from=2023-01-01 --to=2026-11-11
php artisan service:satu-sehat --interval=60
```

Tidak ada backfill sejak 2023 secara otomatis dan tidak ada batas jam operasional.
Data dibaca per 100 kunjungan agar rentang besar tidak dimuat sekaligus ke memori.
Hentikan dan jalankan ulang worker setelah mengganti instalasi atau konfigurasi.

## Cakupan dan sumber data

Worker membedakan **rawat jalan** (`Ralan`, Encounter AMB, `pemeriksaan_ralan`)
dan **rawat inap** (`Ranap`, Encounter IMP, `pemeriksaan_ranap`). Diagnosis,
prosedur dan kategori resep/care plan mengikuti jenis rawat. Kunjungan `Batal`
tidak dikirim. Encounter in-progress/finished, pembaruan resource yang sudah
sukses, dan pembuatan master Medication tidak termasuk worker ini.

| Resource | Sumber |
| --- | --- |
| Patient/Practitioner | Pencarian MPI/master nakes berdasarkan NIK pasien/pegawai |
| Encounter arrived | reg_periksa, pasien, dokter, pegawai, poliklinik |
| Condition | diagnosa_pasien, penyakit, sesuai status rawat |
| Observation | Pemeriksaan pertama sesuai jenis rawat, urut tanggal dan jam |
| Procedure | prosedur_pasien, icd9, sesuai status rawat; semua kode berbeda diproses |
| ClinicalImpression | Penilaian pemeriksaan pertama sesuai jenis rawat |
| MedicationRequest | resep_obat, resep_dokter, mapping obat, master Medication |
| MedicationDispense | detail_pemberian_obat, aturan_pakai, mapping depo; sesuai tanggal/jam resep |
| MedicationStatement | Resep yang memiliki tanggal penyerahan nyata |
| CarePlan | RTL tiap pemeriksaan dan identitas petugas pemeriksaan |

Observation meliputi suhu, respirasi, nadi, SpO2, GCS, tekanan darah, tinggi,
berat, lingkar perut, dan kesadaran. Nilai kosong/tidak numerik tidak diganti angka
buatan. Angka desimal dengan koma dinormalisasi. Diagnosis yang belum ada tidak
menghalangi tahap lain. Waktu klinis dikonversi dari WIB ke UTC dalam payload.

Untuk rawat jalan, mapping lokasi poli diprioritaskan jika tersedia di
`satu_sehat_mapping_lokasi_ralan`; Location ID konfigurasi menjadi fallback.
Untuk rawat inap, Location ID konfigurasi dipakai; pastikan sesuai layanan yang dikirim.
Mapping obat, ID Medication, satuan/rute, dan lokasi depo tetap diperlukan di
SIMRS seperti skrip lama. Identitas yang tidak ditemukan tidak dibuat dengan
data minimal; perbaiki pendaftaran MPI/master nakes terlebih dahulu.

Keterbatasan data sumber lama: waktu tindakan Procedure dan petugas farmasi yang
menyerahkan obat tidak tersedia dari query lama. Worker tidak mengarang waktu
tindakan dari jam registrasi atau mengaku dokter sebagai petugas penyerahan.
Jika profil/use case SATUSEHAT mewajibkan kedua field tersebut, mapping sumber
tambahan perlu disediakan sebelum jenis resource itu dapat diterima API.
Jumlah total obat tidak digunakan sebagai dosis per kali. Aturan berbentuk
`3 x 1` diparsing; aturan lain dipertahankan sebagai teks tanpa frekuensi buatan.

## Penyimpanan dan kompatibilitas

`satu_sehat_service_resources` adalah jurnal utama dengan kunci unik berdasarkan
organisasi, no_rawat dan jenis/item resource. Tidak berisi payload klinis atau
kredensial. Tabel lama `satu_sehat_*_new`, jika tersedia, dibaca untuk melewati
resource yang sudah sukses dan diisi hasil sukses baru agar tampilan lama tetap
kompatibel. Encounter lama harus memiliki patient_id yang cocok dengan MPI.
Struktur tabel lama berbeda antarklinik: uji kompatibilitas skema sebelum produksi.
SQL tabel kontrol tidak membuat atau mengubah tabel sumber/mapping/tabel lama.

Status jurnal:

| Status | Tindakan |
| --- | --- |
| success | Simpan ID; tidak POST ulang |
| failed | Ditolak API; boleh dicoba ulang setelah minimal 5 menit |
| pending | Sudah diklaim sebelum POST; tidak dikirim ulang otomatis |
| uncertain | Timeout, 5xx, 408/409 atau respons sukses tanpa ID/resourceType valid; rekonsiliasi manual |

Klaim pengiriman memakai kunci unik dan update bersyarat di database. Worker
juga memakai OS file lock yang dilepas saat proses berakhir, tanpa TTL harian
atau pembersihan cache global. Jangan menghapus file lock saat proses berjalan.
POST tidak di-retry otomatis oleh HTTP client. Token digunakan ulang sampai
menjelang kedaluwarsa; GET yang mendapat 401 dapat memperbarui token sekali.
TLS verification aktif, redirect HTTP dinonaktifkan, koneksi dan request dibatasi.

Jika proses dihentikan paksa setelah server menerima resource, baris `pending`
tetap ditahan untuk mencegah duplikasi. Periksa resource SATUSEHAT terlebih
dahulu, pastikan kesesuaian organisasi, pasien dan kunjungan. Setelah terverifikasi,
catat resource_id dan ubah jurnal menjadi success; hanya ubah menjadi failed
jika sudah dipastikan resource belum dibuat. Jangan mereset pending/uncertain
secara massal. Encounter baru juga dicari lewat identifier sebelum dibuat.

```sql
SELECT no_rawat, resource_type, resource_key, resource_id,
       status, http_code, attempts, last_attempt_at
FROM satu_sehat_service_resources
WHERE status IN ('pending', 'uncertain', 'failed')
ORDER BY updated_at DESC;
```

Log tidak mencetak NIK, nama pasien, token, secret, payload atau respons klinis.
Respons penolakan hanya dicatat HTTP dan kode OperationOutcome, bukan diagnostics.
Counter `processed` menghitung kunjungan per scan, `succeeded` resource baru sukses,
dan `failed` resource/tahap yang gagal. Run completed berarti scan selesai,
bukan jaminan seluruh resource diterima SATUSEHAT.

## Verifikasi

Terminal dikelompokkan per no_rawat, dengan urutan Encounter, Condition,
Observation (Suhu, Respirasi, Nadi, SpO2, GCS, Tinggi Badan, Berat Badan, Lingkar
Perut, Tekanan Darah, Kesadaran), Procedure, ClinicalImpression, MedicationRequest,
MedicationDispense, MedicationStatement, lalu CarePlan. Seluruh item resep pada
satu tahap diselesaikan sebelum tahap obat berikutnya.
Status MENGIRIM, BERHASIL TERKIRIM beserta ID, SUDAH TERKIRIM, jeda retry, dan
rekonsiliasi ditampilkan sesuai keadaan. Nilai Observation hanya ditampilkan
sebagai DATA TERKIRIM setelah POST baru berhasil; nilai sumber terkini tidak
diklaim sebagai isi resource lama. Scan ulang sukses hanya mencetak status ke
terminal tanpa menambah log database. Judul tahap tetap muncul jika belum ada
data untuk tahap tersebut.

```shell
php artisan test --filter=SatuSehatServiceTest
```

Tes menggunakan SQLite in-memory dan Http::fake/preventStrayRequests, bukan API
produksi. Validasi penerimaan payload sesuai profil/use case tetap perlu dilakukan
di sandbox SATUSEHAT dengan skema SIMRS aktual sebelum menjalankan worker produksi.

Rujukan resmi:
- https://satusehat.kemkes.go.id/platform/docs/id/fhir/resources/encounter/
- https://satusehat.kemkes.go.id/platform/docs/id/fhir/resources/patient/
- https://satusehat.kemkes.go.id/platform/docs/id/fhir/prerequisites/
- https://satusehat.kemkes.go.id/platform/docs/id/api-catalogue/authentication/apis/token/
