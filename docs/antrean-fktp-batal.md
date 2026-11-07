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

Mengikuti panggil antrean: pilih instalasi aktif, siapkan koneksi klinik, ambil
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

Tidak ada perubahan status/hapus kunjungan lokal secara otomatis dan tidak ada
retry otomatis ke BPJS. Log request yang dikirim disimpan di `central.service_logs`
dengan `service_name = antrean_fktp_batal` dan referensi `no_rawat`.
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

## Deployment

Jalankan migration berikut di database central, lalu isi URL batal dari menu:

```shell
php artisan migrate --path=database/migrations/2026_11_07_000001_add_antrean_batal_url_to_base_url_configurations.php
php artisan route:clear
```

Test menggunakan SQLite in-memory dan HTTP fake; tidak membatalkan antrean BPJS nyata:

```shell
php artisan test --filter=Antrean
```
