# Batal antrean dari Khanza

## Endpoint web

`POST /api/integration/antrean-fktp/batal`

Header:

```http
X-Integration-Token: <sama dengan KHANZA_INTEGRATION_TOKEN pada web>
Content-Type: application/json
Accept: application/json
```

Body Khanza:

```json
{
  "no_rawat": "2026/11/07/000001",
  "alasan": "Terjadi perubahan jadwal dokter"
}
```

`no_rawat` wajib string maksimal 17 karakter. `alasan` wajib string maksimal
1000 karakter. Keduanya tidak boleh kosong. Endpoint ini tidak dipanggil melalui
address bar browser: pembatalan adalah POST yang memerlukan token integrasi.

## Alur dan sumber data

Mengikuti panggil antrean: baca konfigurasi tunggal aplikasi, siapkan koneksi SIMRS, ambil
data berdasarkan no_rawat, kirim POST JSON ke BPJS, lalu simpan log hasilnya.

| Field BPJS | Sumber |
| --- | --- |
| `tanggalperiksa` | `clinic.reg_periksa.tgl_registrasi` |
| `kodepoli` | `clinic.maping_poliklinik_pcare.kd_poli_pcare`, melalui `reg_periksa.kd_poli` |
| `nomorkartu` | `clinic.pasien.no_peserta`, melalui `reg_periksa.no_rkm_medis` |
| `alasan` | Body dari Khanza |

Header keluar sesuai spesifikasi batal: `X-cons-id`, `X-timestamp`,
`X-signature`, dan `user_key`. Consumer ID/secret key diambil dari konfigurasi
BPJS klinik aktif. `user_key` memakai **`bpjs_configurations.user_key_antrol`**
di database central. Signature: Base64 HMAC-SHA256 dari `cons_id&timestamp`
menggunakan secret key. Accept dan Content-Type memakai `application/json`.

URL target diambil persis dari `central.base_url_configurations.base_url_antrean_batal`.
Isi **URL endpoint lengkap** di menu **Konfigurasi > Base URL BPJS > Edit >
Base URL Antrean Batal**, termasuk service name dan `/antrean/batal`.
URL tidak ditambahi path lagi. Field boleh kosong bila fitur batal belum digunakan.
URL kosong akan membuat permintaan batal gagal; URL Add/Panggil tidak diubah.

Tidak ada retry otomatis ke BPJS. Log request yang dikirim disimpan di `central.service_logs`
dengan `service_name = antrean_fktp_batal` dan referensi `no_rawat`.
Jika respons BPJS memiliki `metadata.code = 200`, aplikasi juga menyimpan
`no_rawat`, `status = success`, dan `waktu_batal` (tanggal dan jam UTC+07:00)
ke `clinic.antrean_fktp_cancellations`, yaitu database SIMRS klinik aktif,
mengikuti koneksi penyimpanan `config_pcare`. Waktu dicatat saat respons sukses diterima.
Pada respons sukses tersebut, `reg_periksa.stts` untuk `no_rawat` yang sama
diubah menjadi `Batal`. Penyimpanan hasil dan update status memakai satu transaksi
database SIMRS agar keduanya tersimpan bersama atau dibatalkan bersama jika gagal.
Respons BPJS selain kode 200 tidak mengubah status kunjungan.
Setiap respons sukses menambah satu baris; respons gagal tidak disimpan di tabel ini.
Respons tidak menampilkan kredensial BPJS.

## Struktur file

```text
routes/api.php
app/Http/Controllers/Api/AntreanFktpCancelController.php
app/Services/AntreanFktpCancelService.php
app/Models/BaseUrlConfiguration.php
app/Http/Controllers/BaseUrlConfigurationController.php
resources/views/configurations/base-url/form.blade.php
resources/views/configurations/base-url/index.blade.php
database/migrations/2026_11_07_000001_add_antrean_batal_url_to_base_url_configurations.php
tests/Feature/Api/AntreanFktpCancelTest.php
tests/Feature/AntreanEndpointConfigurationTest.php
```

## Pemanggilan Java Khanza

Gunakan HTTP client JSON yang sudah dipakai untuk panggil antrean, dengan URL:

```java
URL obj = new URL("http://" + koneksiDB.HOSTCALL() + "/"
    + koneksiDB.FOLDERCALL()
    + "/public/api/integration/antrean-fktp/batal");
```

Gunakan metode POST, header token di atas, dan serializer JSON untuk body
`no_rawat`/`alasan` agar kutip atau baris baru pada alasan di-escape dengan benar.
Jangan menaruh token di query URL. Jangan kirim `status` atau `waktu` ke endpoint
batal. Bagian `/public` dihilangkan jika document root sudah menunjuk folder public.

Respons mengikuti pola panggil antrean: hasil service/BPJS dibungkus HTTP 200 dan
kode hasil diperiksa pada `metadata.code`, bukan hanya status HTTP. Contoh respons
sukses (pesan aktual mengikuti BPJS):

```json
{"metadata":{"code":200,"message":"Ok"}}
```

Data lokal tidak ditemukan menghasilkan `metadata.code = 404` tanpa menghubungi
BPJS. Gangguan koneksi menghasilkan `metadata.code = 500`. Respons bukan JSON
menghasilkan `metadata.code = 502`. Token salah menghasilkan HTTP 401, input
tidak valid HTTP 400, dan kegagalan konfigurasi/pemrosesan HTTP 500.

## Debug dari Java Khanza

Sesudah web mencoba mengirim request BPJS, header respons berikut tersedia
termasuk ketika BPJS mengembalikan `metadata.code = 201`:

```text
X-BPJS-Debug-Url
X-BPJS-Debug-Cons-Id
X-BPJS-Debug-Timestamp
X-BPJS-Debug-Signature       # generated (hidden), bukan nilai signature
X-BPJS-Debug-User-Key        # configured (hidden) atau empty
X-BPJS-Payload-Tanggal
X-BPJS-Payload-Kode-Poli
X-BPJS-Payload-Nomor-Kartu
X-BPJS-Payload-Alasan
```

Alasan pada header memakai percent encoding agar Unicode/baris baru tidak
merusak header HTTP. Payload JSON yang dikirim ke BPJS tetap berisi teks asli.
Contoh pembacaan:

```java
String alasanHeader = connection.getHeaderField("X-BPJS-Payload-Alasan");
String alasanAktual = alasanHeader == null ? null
    : java.net.URLDecoder.decode(alasanHeader, "UTF-8");
```

Debug batal tidak memiliki `status`/`waktu`; hapus pembacaan kedua field panggil
itu dari tampilan debug batal. Header dapat tidak tersedia jika validasi gagal,
kunjungan lokal tidak ditemukan, atau konfigurasi gagal sebelum pengiriman.
Signature dan user key asli tidak dikembalikan ke client. HTTP 200 dari Laravel
tidak berarti pembatalan berhasil; periksa `metadata.code` dan `metadata.message`.
Jika BPJS menjawab data tidak ditemukan, cocokkan tanggal, kode poli PCare,
nomor kartu, dan lingkungan URL layanan dengan request add yang berhasil.
Jangan mengirim ulang pembatalan otomatis hanya untuk mendapatkan debug.

## Deployment

Jalankan migration berikut di database central, lalu isi URL batal dari menu:

```shell
php artisan migrate --path=database/migrations/2026_11_07_000001_add_antrean_batal_url_to_base_url_configurations.php
php artisan route:clear
```

Jalankan `database/sql/antrean_fktp_cancellations.sql` pada setiap database SIMRS
sebelum menggunakan fitur pembatalan. Migration central tanggal 2026-11-11
adalah tabel versi lama; tabel itu tidak lagi dipakai dan tidak dihapus otomatis.
Jika tabel sudah ada pada database SIMRS dengan dua kolom, tambahkan kolom dengan:

```sql
ALTER TABLE antrean_fktp_cancellations
    ADD COLUMN waktu_batal DATETIME NULL;
```

Kolom nullable pada tabel lama mempertahankan baris lama yang waktu pembatalannya
tidak diketahui. Baris baru selalu diisi waktunya oleh aplikasi.

Test menggunakan SQLite in-memory dan HTTP fake; tidak membatalkan antrean BPJS nyata:

```shell
php artisan test --filter=Antrean
```
