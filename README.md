# Courier API

REST API untuk master data **kurir (courier)**, dibangun dengan Laravel 13 dan PHP 8.3.
Hanya API (JSON), tanpa UI. Seluruh endpoint ada di bawah prefix `/api/v1`.

## Isi

- [Instalasi](#instalasi)
- [Menjalankan test](#menjalankan-test)
- [Struktur data](#struktur-data)
- [Endpoint](#endpoint)
- [GET /couriers (index)](#get-apiv1couriers)
- [POST & PUT/PATCH (validasi)](#post-apiv1couriers--putpatch-apiv1couriersid)
- [Keputusan desain](#keputusan-desain)
- [Struktur kode](#struktur-kode)

## Instalasi

Kebutuhan: PHP 8.3+ (dengan ekstensi `pdo_sqlite`) dan Composer. Database default adalah SQLite, jadi tidak ada
server database yang perlu disiapkan.

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed   # seed: 50 kurir contoh, termasuk "Budiono Hadi Agung"
php artisan serve            # http://localhost:8000
```

Contoh pemakaian:

```bash
curl "http://localhost:8000/api/v1/couriers?search=budi+agung"
curl "http://localhost:8000/api/v1/couriers?level=2,3&sort=created_at&direction=desc"
```

## Menjalankan test

```bash
php artisan test
```

Test memakai SQLite in-memory (lihat `phpunit.xml`), jadi database development tidak tersentuh.
Ada 112 test:

| File                                                                 | Yang diuji                                                                                          |
|----------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------|
| [CourierIndexTest](tests/Feature/Api/V1/CourierIndexTest.php)        | Pagination, sort default & override, search, filter level, kombinasi, validasi query                 |
| [CourierShowTest](tests/Feature/Api/V1/CourierShowTest.php)          | Semua field dikembalikan, 404                                                                        |
| [CourierStoreTest](tests/Feature/Api/V1/CourierStoreTest.php)        | Data tersimpan di database, default status, batas level, semua aturan validasi, unique               |
| [CourierUpdateTest](tests/Feature/Api/V1/CourierUpdateTest.php)      | PUT vs PATCH, data berubah di database, unique mengabaikan diri sendiri, validasi, 404               |
| [CourierDestroyTest](tests/Feature/Api/V1/CourierDestroyTest.php)    | Data hilang dari database, data lain aman, 404                                                       |
| [CourierScopesTest](tests/Unit/CourierScopesTest.php)                | Scope `search`, `ofLevels`, `sortedBy` dan cast atribut pada model                                   |

## Struktur data

Tabel `couriers` ([migration](database/migrations/2026_09_29_084008_create_couriers_table.php)):

| Kolom                  | Tipe                | Keterangan                                                          |
|------------------------|---------------------|---------------------------------------------------------------------|
| `id`                   | bigint, PK          |                                                                     |
| `name`                 | string(255)         | Nama kurir (di-index, dipakai untuk sort dan search)                |
| `phone`                | string(20), unique  | Nomor telepon, boleh diawali `+`                                    |
| `email`                | string, unique, null|                                                                     |
| `level`                | tinyint unsigned    | Level kurir **1–5** (di-index)                                      |
| `vehicle_type`         | string(20)          | `bicycle`, `motorcycle`, `car`, `van`                               |
| `vehicle_plate_number` | string(15), null    | Plat nomor (kosong untuk sepeda)                                    |
| `status`               | string(20)          | `active` (default), `inactive`, `suspended`                         |
| `address`              | text, null          |                                                                     |
| `created_at`           | timestamp           | Tanggal kurir didaftarkan (di-index)                                |
| `updated_at`           | timestamp           |                                                                     |

## Endpoint

| Method      | URL                       | Keterangan                              | Response          |
|-------------|---------------------------|-----------------------------------------|-------------------|
| `GET`       | `/api/v1/couriers`        | Daftar kurir (pagination, sort, filter) | `200`             |
| `POST`      | `/api/v1/couriers`        | Tambah kurir                            | `201`             |
| `GET`       | `/api/v1/couriers/{id}`   | Detail kurir, semua field               | `200`             |
| `PUT/PATCH` | `/api/v1/couriers/{id}`   | Ubah kurir                              | `200`             |
| `DELETE`    | `/api/v1/couriers/{id}`   | Hapus kurir                             | `204` (tanpa body)|

Kegagalan validasi menghasilkan `422` dengan format standar Laravel
(`{"message": "...", "errors": {"field": ["..."]}}`). Kurir yang tidak ada menghasilkan `404`.

### GET /api/v1/couriers

Semua parameter bersifat opsional dan bisa dikombinasikan.

| Parameter   | Contoh                 | Keterangan                                                                                              | Default |
|-------------|------------------------|---------------------------------------------------------------------------------------------------------|---------|
| `search`    | `budi+agung`           | Nama harus mengandung **semua** kata, urutan bebas, tidak peka huruf besar/kecil. Max 100 karakter.     | –       |
| `level`     | `2,3`                  | Hanya kurir dengan level tersebut. Daftar dipisah koma, tiap nilai harus 1–5.                            | semua   |
| `sort`      | `created_at`           | Urutkan berdasarkan `name` atau `created_at` (tanggal didaftarkan).                                      | `name`  |
| `direction` | `desc`                 | `asc` atau `desc`.                                                                                       | `asc`   |
| `per_page`  | `25`                   | Jumlah data per halaman, 1–100.                                                                          | `15`    |
| `page`      | `2`                    | Nomor halaman.                                                                                           | `1`     |

- `?search=budi+agung` cocok dengan **Budiono Hadi Agung**, tetapi tidak dengan "Budi Santoso" atau "Agung Prasetyo".
- Karakter `%` dan `_` pada `search` diperlakukan sebagai teks biasa, bukan wildcard.
- Untuk urutan yang sama (mis. nama kembar) hasil diurutkan lagi berdasarkan `id`, sehingga pagination tetap stabil.
- Link pagination (`links.next`, dst.) sudah membawa parameter yang sedang aktif.
- Parameter yang kosong (`?level=&sort=`) dianggap tidak dikirim.

Contoh response `GET /api/v1/couriers?search=budi+agung`:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Budiono Hadi Agung",
      "phone": "081234567890",
      "email": "budiono@example.com",
      "level": 2,
      "vehicle_type": "motorcycle",
      "vehicle_plate_number": "B 1234 XYZ",
      "status": "active",
      "address": "Jl. Merdeka No. 1, Jakarta",
      "created_at": "2026-09-29T08:47:10.000000Z",
      "updated_at": "2026-09-29T08:47:10.000000Z"
    }
  ],
  "links": { "first": "...?page=1", "last": "...?page=1", "prev": null, "next": null },
  "meta": { "current_page": 1, "per_page": 15, "last_page": 1, "total": 1, "from": 1, "to": 1, "path": "...", "links": [] }
}
```

`GET /api/v1/couriers/{id}` mengembalikan objek yang sama di dalam kunci `data`.

### POST /api/v1/couriers & PUT/PATCH /api/v1/couriers/{id}

| Field                  | POST       | PUT        | PATCH      | Aturan                                                                      |
|------------------------|------------|------------|------------|-----------------------------------------------------------------------------|
| `name`                 | wajib      | wajib      | opsional   | string, 2–255 karakter                                                      |
| `phone`                | wajib      | wajib      | opsional   | 9–15 digit, boleh diawali `+`, **unik**                                     |
| `email`                | opsional   | opsional   | opsional   | format email valid, max 255, **unik**                                       |
| `level`                | wajib      | wajib      | opsional   | integer 1–5                                                                 |
| `vehicle_type`         | wajib      | wajib      | opsional   | `bicycle`, `motorcycle`, `car`, `van`                                       |
| `vehicle_plate_number` | opsional   | opsional   | opsional   | string, max 15                                                              |
| `status`               | opsional   | opsional   | opsional   | `active`, `inactive`, `suspended` (default `active` saat POST)              |
| `address`              | opsional   | opsional   | opsional   | string, max 1000                                                            |

Saat update, aturan unik mengabaikan kurir itu sendiri, jadi mengirim ulang `phone` / `email` yang sama tidak error.
Field opsional bisa dikosongkan dengan mengirim `null`.

## Keputusan desain

- **API versioning & Eloquent API Resource.** Route ada di `/api/v1`; bentuk JSON diatur oleh `CourierResource`, bukan
  langsung dari model.
- **Validasi lewat Form Request.** `IndexCourierRequest` juga memvalidasi query string (`level`, `sort`, `per_page`, ...)
  sehingga input yang aneh ditolak dengan `422`, bukan diam-diam diabaikan.
- **Logika query ada di model** sebagai scope (`search`, `ofLevels`, `sortedBy`) supaya controller tetap tipis dan
  logikanya bisa diuji sendiri (`CourierScopesTest`).
- **Search tidak bergantung pada perilaku collation database.** Pencarian memakai `LOWER(name) LIKE ... ESCAPE '!'`
  sehingga hasilnya sama di SQLite, MySQL, dan PostgreSQL.
- **Level** disimpan sebagai angka 1–5 (batas ada di konstanta `Courier::MIN_LEVEL` / `MAX_LEVEL`). Tipe kendaraan dan
  status memakai PHP enum (`VehicleType`, `CourierStatus`).
- **Tanggal didaftarkan** = `created_at`; tidak dibuat kolom terpisah.
- **Hapus bersifat permanen** (tanpa soft delete), sesuai brief.
- **Autentikasi tidak termasuk** dalam brief, jadi seluruh endpoint terbuka.

## Struktur kode

```
app/
├── Enums/                          VehicleType, CourierStatus
├── Http/
│   ├── Controllers/Api/V1/         CourierController
│   ├── Requests/Api/V1/            Index-, Store-, UpdateCourierRequest
│   └── Resources/Api/V1/           CourierResource
└── Models/Courier.php              scope search / ofLevels / sortedBy
database/                           migration, factory, seeder
routes/api.php                      apiResource('couriers') di bawah /api/v1
tests/                              Feature/Api/V1/*, Unit/CourierScopesTest
```
