# PRD — ARMoS Tools 2.0 (Laravel + AdminLTE)

**Product Requirements Document**  
**Versi:** 1.0  
**Tanggal:** 2026-08-11  
**Status:** Draft untuk implementasi  
**Sumber referensi fungsional:** aplikasi Flask `armos-tools` (`app.py` + templates)

---

## 1. Ringkasan Produk

### 1.1 Latar Belakang

ARMoS Utilities (v1) adalah tool internal berbasis Flask di mana **semua halaman frontend dan logic/service berada di `app.py`**. Tim ingin membangun ulang sebagai **ARMoS Tools 2.0** dengan:

| Aspek | v1 (Sekarang) | v2 (Target) |
|-------|---------------|-------------|
| Framework | Flask 3 | Laravel 11 |
| Frontend | Bootstrap standalone per halaman | Blade + **AdminLTE 3** |
| Auth | Supabase `log_user_auth` | Tabel Laravel **`tools_user`** |
| Captcha login | 6 digit angka | **Soal perkalian** (`a × b`) |
| Backend logic | Function di `app.py` + modul Python | Controller + Service Laravel |
| Deploy | Gunicorn | Docker di `nuradigital/services/armos-tools` |

### 1.2 Tujuan Produk

1. Menyediakan 14 menu utilitas operasional TMS ARMoS (parity fitur dengan v1).
2. Memisahkan jelas **Frontend (Blade/AdminLTE)** dan **Backend (API/Service Laravel)**.
3. Login lokal via tabel `tools_user` tanpa dependensi Supabase.
4. Tetap aman: session auth + captcha perkalian di login.

### 1.3 Non-Tujuan (Out of Scope v2.0 Fase Awal)

- Mengganti / rewrite core TMS ARMoS.
- Multi-tenant penuh.
- Role & permission granular (cukup semua user login punya akses semua menu).
- Migrasi historis log SQLite (cukup kompatibel path `data_log/`).
- UI Admin untuk edit koneksi DB (tetap `.env` dulu).

### 1.4 Pengguna

| Persona | Kebutuhan |
|---------|-----------|
| Ops / Support TMS | Cari & koreksi data order, lokasi, reconciliation, qty, dll. |
| Integrasi | Upload feed order preprod, cek log API |
| Data / BI | Sync fact table ke warehouse, export CSV |

### 1.5 Sukses Criteria

- [ ] Login dengan `nama` + password hash + captcha perkalian berhasil.
- [ ] Semua 14 menu punya halaman AdminLTE + API yang dipanggil dari frontend.
- [ ] Response API kompatibel dengan kontrak di dokumen ini (status/data/message).
- [ ] Write operation hanya ke DB/env yang sesuai spesifikasi menu.
- [ ] Deployable via Docker Compose di stack NuraDigital.

---

## 2. Arsitektur Target

```
┌─────────────────────────────────────────────────────────────┐
│  Browser — AdminLTE (Blade)                                  │
│  Login | Dashboard | 14 Menu Pages | Coming Soon placeholders │
└───────────────────────────┬─────────────────────────────────┘
                            │ HTTP (session cookie + CSRF)
┌───────────────────────────▼─────────────────────────────────┐
│  Laravel 11 — services/armos-tools                           │
│  Controllers → Services → DB / External API                  │
└───────┬───────────────┬──────────────────┬──────────────────┘
        │               │                  │
   App DB          TMS Postgres      External API
   tools_user      MAIN/PREPROD/     AUTH_URL +
                   PROD / DB_A/B     FEED_ORDER_URL
```

### 2.1 Lokasi Project

| Item | Path |
|------|------|
| Kode baru | `/home/nugihiday/nuradigital/services/armos-tools` |
| Referensi v1 | `E:\Documents\Working Area\Logicnesia\armos-tools` (Flask) |
| Traefik host (usulan) | `http://armos-tools.lokal.test` |

### 2.2 Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 11, PHP 8.2+ |
| Frontend | Blade, AdminLTE 3, Bootstrap 4/5 (sesuai AdminLTE), vanilla JS / jQuery |
| App DB | MySQL/PostgreSQL container NuraDigital (tabel `tools_user`) |
| TMS DB | PostgreSQL eksternal (via `.env` named connections) |
| Excel | PhpSpreadsheet / Maatwebsite Excel |
| HTTP client | Laravel `Http` facade |
| Queue (Sync) | Laravel Queue (Fase Sync Manager) |

---

## 3. Autentikasi & Keamanan

### 3.1 Tabel `tools_user`

| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | BIGINT PK AI | Auto increment |
| `nama` | VARCHAR unique | Dipakai sebagai username login |
| `password` | VARCHAR | Hash bcrypt/argon (Laravel hasher) |
| `created_at` / `updated_at` | timestamp | Disarankan untuk Eloquent |

**Bukan** memakai Supabase. Login field = `nama`, password di-hash.

### 3.2 Halaman Login (Frontend)

**Komponen UI wajib:**

| Elemen | Keterangan |
|--------|------------|
| Input `nama` | Text, required |
| Input `password` | Password, required |
| Label captcha | Teks soal: `{{ a }} × {{ b }} = ?` |
| Input `captcha` | Angka jawaban, required |
| Tombol Submit | "Masuk" |
| Alert error | Captcha salah / kredensial salah |
| Layout | Guest layout AdminLTE (tanpa sidebar) |

**Captcha:**

- Generate `a = random(1..9)`, `b = random(1..9)`.
- Simpan jawaban `a*b` di session.
- Salah → regenerate soal baru.
- Benar → baru cek `Auth::attempt(['nama' => ..., 'password' => ...])`.

### 3.3 Route Auth

| Method | Path | Frontend | Backend aksi |
|--------|------|----------|--------------|
| GET | `/login` | Form login + soal captcha | Generate/simpan captcha session |
| POST | `/login` | Submit form | Validasi captcha + auth `tools_user` |
| POST/GET | `/logout` | Tombol logout di navbar | `Auth::logout()`, invalidate session |

### 3.4 Gate

Semua route kecuali `/login`, assets publik, wajib middleware `auth`.  
Guest yang belum login → redirect `/login`.

---

## 4. Frontend — Spesifikasi Halaman

### 4.1 Layout Global (AdminLTE)

**Layout App (setelah login):**

| Area | Isi |
|------|-----|
| Navbar | Judul app, nama user (`nama`), tombol Home, tombol Logout |
| Sidebar | 14 menu + Dashboard |
| Content | `@yield('content')` per halaman |
| Footer | Opsional versi app |

**Layout Guest:**

- Hanya form login (centered card AdminLTE).

**Dashboard `/`:**

- Daftar 14 tools (mirip `index.html` v1) sebagai list/link ke `/menu/...`.
- Bisa berupa kartu ringkas di content AdminLTE.

### 4.2 Inventori Halaman Frontend

| # | Route Halaman | View Blade (usulan) | Komponen UI utama | API yang di-hit dari halaman |
|---|----------------|---------------------|-------------------|------------------------------|
| — | `/login` | `auth/login.blade.php` | Form nama, password, captcha perkalian | `POST /login` (form, bukan JSON API) |
| — | `/` | `dashboard.blade.php` | List 14 menu | — |
| 1 | `/menu/update-lokasi` | `menus/update_lokasi.blade.php` | Dropdown warehouse, input faktur, tabel order, modal pilih lokasi (Select2), tombol ubah | `GET /api/orders`, `GET /api/locations`, `POST /api/orders/update-location` |
| 2 | `/menu/uncheck-reconciliation` | `menus/uncheck_recon.blade.php` | Input faktur, tabel recon, tombol uncheck | `GET /api/reconciliation`, `POST /api/reconciliation/uncheck` |
| 3 | `/menu/log-viewer` | `menus/log_viewer.blade.php` | Dropdown folder tanggal, dropdown event, filter request, tabel log + pagination | `GET /api/log/folders`, `GET /api/log/events`, `GET /api/log/search` |
| 4 | `/menu/product-to-route` | `menus/product_to_route.blade.php` | Input SKU, start/end date, tabel hasil | `GET /api/product-to-route` |
| 5 | `/menu/wms-integrasi` | `menus/wms_integrasi.blade.php` | Input faktur, tabel order, form update integration id | `GET /api/wms-integration`, `POST /api/wms-integration/update` |
| 6 | `/menu/convert-send` | `menus/convert_send.blade.php` | Upload `.xlsx`, textarea JSON hasil, modal step log | `POST /api/convert-send` |
| 7 | `/menu/sync-manager` | `menus/sync_manager.blade.php` | Pilih sync_type, date range, tombol run, status job poll, tabel history | `POST /api/sync/run`, `GET /api/sync/job/{id}`, `GET /api/sync/status` |
| 8 | `/menu/update-qty-unloading` | `menus/update_qty_unloading.blade.php` | Warehouse, faktur, SKU, tabel detail, form update qty | `GET /api/qty-unloading/find`, `POST /api/qty-unloading/update` |
| 9 | `/menu/hapus-driver-cost` | `menus/hapus_driver_cost.blade.php` | Input manifest, tabel cost + pagination, tombol hapus | `GET /api/driver-cost/list`, `POST /api/driver-cost/delete` |
| 10 | `/menu/import-lokasi` | `menus/import_lokasi.blade.php` | Pilih env preprod/prod, upload Excel, log proses, link download log | `POST /api/import-lokasi`, `GET /api/import-lokasi/download-log` |
| 11 | `/menu/check-order-status` | `menus/check_order_status.blade.php` | Input DO/faktur, tabel LIST order, tabel detail, tab Product vs Inventory | `GET /api/check-order-status/orders`, `.../order-details`, `.../product-vs-inventory` |
| 12 | `/menu/ubah-order-status` | `menus/ubah_order_status.blade.php` | Pilih env, input order number, form edit status/integration/delivery_date | `GET /api/ubah-order-status/search`, `POST /api/ubah-order-data/update` |
| 13 | `/menu/export-data-csv` | `menus/export_data_csv.blade.php` | Pilih env + tipe data, generate, tombol download | `POST /api/export-data-csv/generate`, `GET /api/export-data-csv/download` |
| 14 | `/menu/update-order-on-route` | `menus/update_order_on_route.blade.php` | Pilih env, input manifest, tabel order, form update route status | `GET /api/update-order-on-route/search`, `POST /api/update-order-on-route/update` |
| — | Error 404/500 | `errors/*.blade.php` | Pesan error + link home | — |

### 4.3 Pola Interaksi Frontend Umum

1. Halaman Blade di-render server-side (auth + data awal seperti dropdown warehouse).
2. Aksi user (cari / simpan / upload) memanggil **API JSON** via `fetch` / `$.ajax`.
3. Response standar: `{ status: 200|400|500, message?: string, data?: any, ... }`.
4. Loading spinner saat request; alert/modal untuk sukses/error.
5. CSRF token Laravel wajib di setiap POST (header `X-CSRF-TOKEN` atau meta tag).

### 4.4 Dependensi UI Frontend

| Library | Dipakai untuk |
|---------|----------------|
| AdminLTE 3 | Shell layout, sidebar, cards |
| Bootstrap (bundled AdminLTE) | Grid, modal, form, table |
| Select2 | Dropdown lokasi (Menu 1) |
| jQuery (jika AdminLTE default) | Select2 + beberapa interaksi |

---

## 5. Backend — Katalog API Lengkap

Semua API di bawah **wajib auth session**, kecuali dinyatakan lain.

Kontrak umum:

```json
{
  "status": 200,
  "message": "opsional",
  "data": []
}
```

Error: `status` 400/500 + `message`.

---

### 5.0 Auth (bukan JSON API utama)

| Method | Endpoint | Request | Response |
|--------|----------|---------|----------|
| GET | `/login` | — | HTML form |
| POST | `/login` | form: `nama`, `password`, `captcha` | Redirect `/` atau HTML error |
| GET/POST | `/logout` | — | Redirect `/login` |

---

### Menu 1 — Update Lokasi Customer

**DB:** `DATABASE_MAIN`  
**Halaman SSR juga load:** daftar warehouse (`WH_TYPE`)

| Method | Endpoint | Query / Body | Response | Dipakai frontend untuk |
|--------|----------|--------------|----------|------------------------|
| GET | `/api/orders` | `faktur_id`, `warehouse_id` | `{ status, data: [{ faktur_date, faktur_id, order_id, warehouse_id, mst_location_child_id, code, name }] }` | Isi tabel order |
| GET | `/api/locations` | — | `{ status, data: [{ mst_location_child_id, code, name }] }` | Isi Select2 modal |
| POST | `/api/orders/update-location` | JSON `{ faktur_id, customer_id: int }` | `{ status, affected }` | Simpan lokasi baru |

**Write:** `UPDATE "order" SET customer_id = ? WHERE faktur_id = ?`

---

### Menu 2 — Uncheck Document Reconciliation

**DB:** MAIN

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| GET | `/api/reconciliation` | `faktur_id` | `{ status, data: [odr.* + faktur_id] }` | Tabel recon |
| POST | `/api/reconciliation/uncheck` | `{ order_id }` | `{ status, affected }` | Hapus baris recon |

**Write:** `DELETE FROM order_document_reconciliation WHERE order_id = ?`

---

### Menu 3 — Log Viewer

**Sumber:** SQLite lokal `data_log/{DDMMYYYY}/{slug}.db`

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| GET | `/api/log/folders` | — | daftar folder tanggal | Dropdown tanggal |
| GET | `/api/log/events` | — | daftar event + config filter | Dropdown event |
| GET | `/api/log/search` | folder, event, filter, page/limit | rows log | Tabel + pagination |

**External hit:** tidak ada (read file lokal). Data diisi cron/job export dari `sys_api_request_log`.

---

### Menu 4 — Product To Route

**DB:** MAIN (read only)

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| GET | `/api/product-to-route` | `sku`, `start_date`, `end_date` | rows route/order/qty | Tabel hasil |

---

### Menu 5 — Update WMS Integrasi

**DB:** MAIN

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| GET | `/api/wms-integration` | `faktur_id` | data order | Tabel |
| POST | `/api/wms-integration/update` | `{ order_id, order_integration_id }` | `{ status, affected }` | Simpan |

**Write:** `UPDATE "order" SET order_integration_id = ? WHERE order_id = ?`

---

### Menu 6 — Convert & Send (Preprod)

**DB app:** file storage  
**External API yang di-hit backend:**

| Step | External call | Env |
|------|---------------|-----|
| 1 | Upload Excel → convert JSON (internal) | — |
| 2 | `POST {AUTH_URL}` login integrasi → Bearer token | `AUTH_URL`, `SEND_ORDER_USERNAME`, `SEND_ORDER_PASSWORD` |
| 3 | Per order: `POST {FEED_ORDER_URL}` + Bearer | `FEED_ORDER_URL` |

| Method | Endpoint | Request | Response | UI |
|--------|----------|---------|----------|-----|
| POST | `/api/convert-send` | `multipart/form-data` field `file` (.xlsx) | `{ status, message, steps: [{status,message}], converted_json }` | Textarea JSON + modal langkah |

**Sheet Excel wajib:** `order_data`, `order_detail`.

---

### Menu 7 — Sync Manager

**DB:** `DB_A` (source) → `DB_B` (warehouse)  
**External:** tidak ada HTTP eksternal; pure DB ETL.

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| POST | `/api/sync/run` | `{ sync_type: fact_order\|fact_delivery\|both, date_from?, date_to? }` | `{ status, job_id }` | Mulai job |
| GET | `/api/sync/job/{job_id}` | — | status job in-progress/done/fail | Polling |
| GET | `/api/sync/status` | — | dashboard + history `tms_sync_log` | Ringkasan & tabel history |

**Target write DB B:** `tms_fact_order`, `tms_fact_delivery`, `tms_sync_log`.

---

### Menu 8 — Update Qty Unloading

**DB:** MAIN

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| GET | `/api/qty-unloading/find` | `warehouse_id`, `faktur_id`, `sku` | baris order_detail | Tabel |
| POST | `/api/qty-unloading/update` | `{ order_detail_id, quantity_unloading }` | `{ status, affected }` | Simpan |

**Write:** `UPDATE order_detail SET quantity_unloading = ? WHERE order_detail_id = ?`

---

### Menu 9 — Hapus Driver Cost

**DB:** MAIN

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| GET | `/api/driver-cost/list` | `manifest_reference`, `page` | rows + pagination | Tabel |
| POST | `/api/driver-cost/delete` | `{ order_cost_id }` | `{ status, affected }` | Hapus |

**Write:** `DELETE FROM order_cost WHERE order_cost_id = ?`

---

### Menu 10 — Import Lokasi

**DB:** PREPROD atau PROD (pilihan UI)

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| POST | `/api/import-lokasi` | multipart `file` + `env=preprod\|prod` | messages + `log_data_filename` | Log proses |
| GET | `/api/import-lokasi/download-log` | `filename` | file Excel log | Download |

**Write:** insert `mst_location_parent` / `mst_location_child` (skip jika sudah ada sesuai rule bisnis).

---

### Menu 11 — Check Order Status

**DB:** MAIN (read only)

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| GET | `/api/check-order-status/orders` | `faktur_id` | list order (+ username created/updated) | Tabel LIST Order |
| GET | `/api/check-order-status/order-details` | `order_id` | list order_detail | Tabel detail |
| GET | `/api/check-order-status/product-vs-inventory` | `faktur_id` | sku, qty, Full Fill / Not Full Fill | Tab inventory |

---

### Menu 12 — Ubah Order Data

**DB:** PREPROD / PROD

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| GET | `/api/ubah-order-status/search` | `env`, `order_number` | list order | Form edit |
| POST | `/api/ubah-order-data/update` | `{ env, order_id, status?, order_integration_id?, delivery_date? }` | `{ status, affected }` | Simpan |

**Field status valid:**  
`new`, `loading`, `ready_to_deliver`, `in_delivery`, `completed`, `skip`, `rejected`, `hold`, `failed`, `return_to_wms`, `inactive`, `in_optimization`

**Audit:** set `updated_date`, `updated_by` = `nama` user login.

---

### Menu 13 — Export Data CSV

**DB:** PREPROD / PROD

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| POST | `/api/export-data-csv/generate` | `{ env, type }` | `{ status, filename }` | Generate |
| GET | `/api/export-data-csv/download` | `filename` | file CSV | Download |

**Tipe `type`:**

| Key | Sumber |
|-----|--------|
| `dataproduct` | `mst_product` (+ stock di preprod) |
| `datavehicle` | `mst_vehicle` |
| `lovconfig` | `mst_list_of_values` |
| `masterlocation` | `mst_location_parent` |
| `childlocation` | `mst_location_child` |

---

### Menu 14 — Update Order On Route

**DB:** PREPROD / PROD

| Method | Endpoint | Param | Response | UI |
|--------|----------|-------|----------|-----|
| GET | `/api/update-order-on-route/search` | `env`, `manifest_reference` | order + route fields | Tabel |
| POST | `/api/update-order-on-route/update` | `{ env, manifest_reference, status?, manifest_integration_id? }` | `{ status, affected }` | Update **tabel `route`** |

**Status route valid:**  
`new`, `loading`, `ready_to_deliver`, `in_delivery`, `delivery_success`, `delivery_completed`, `rejected`

---

## 6. Matriks: Frontend → API → Sistem Eksternal

| Menu | Frontend hit (internal Laravel API) | Sistem di balik API |
|------|-------------------------------------|---------------------|
| Login | Form `/login` | App DB `tools_user` |
| 1 Update Lokasi | `/api/orders`, `/api/locations`, `/api/orders/update-location` | Postgres MAIN |
| 2 Uncheck Recon | `/api/reconciliation`, `/api/reconciliation/uncheck` | Postgres MAIN |
| 3 Log Viewer | `/api/log/folders`, `/events`, `/search` | SQLite `data_log/` |
| 4 Product To Route | `/api/product-to-route` | Postgres MAIN |
| 5 WMS Integrasi | `/api/wms-integration`, `/update` | Postgres MAIN |
| 6 Convert & Send | `/api/convert-send` | File + **AUTH_URL** + **FEED_ORDER_URL** |
| 7 Sync Manager | `/api/sync/run`, `/job/{id}`, `/status` | Postgres **DB_A → DB_B** |
| 8 Qty Unloading | `/api/qty-unloading/find`, `/update` | Postgres MAIN |
| 9 Driver Cost | `/api/driver-cost/list`, `/delete` | Postgres MAIN |
| 10 Import Lokasi | `/api/import-lokasi`, `/download-log` | Postgres PREPROD/PROD |
| 11 Check Order | `/api/check-order-status/*` (3 endpoint) | Postgres MAIN |
| 12 Ubah Order | `/api/ubah-order-status/search`, `/api/ubah-order-data/update` | Postgres PREPROD/PROD |
| 13 Export CSV | `/api/export-data-csv/generate`, `/download` | Postgres PREPROD/PROD |
| 14 Order On Route | `/api/update-order-on-route/search`, `/update` | Postgres PREPROD/PROD |

### 6.1 Daftar External HTTP yang benar-benar di-hit

Hanya dari **Menu 6** (dan opsional script WMS inventory di luar scope UI):

| Nama | Method | URL dari env | Dipakai oleh |
|------|--------|--------------|--------------|
| ARMoS Auth | POST | `AUTH_URL` | Convert & Send — ambil token |
| ARMoS Feed Order | POST | `FEED_ORDER_URL` | Convert & Send — kirim tiap order |

**Catatan:** Login user tool **tidak** lagi memanggil Supabase.

---

## 7. Kebutuhan Environment

### 7.1 App

```
APP_NAME=ARMoS Tools
APP_URL=http://armos-tools.lokal.test
APP_KEY=
DB_CONNECTION=mysql   # atau pgsql — App DB untuk tools_user
DB_HOST=mysql
DB_DATABASE=armos_tools
DB_USERNAME=nuradigital
DB_PASSWORD=nuradmin
```

### 7.2 TMS

```
WH_TYPE=
DATABASE_MAIN_HOST / PORT / NAME / USERNAME / PASS
DATABASE_PREPROD_*
DATABASE_PROD_*
DB_A_*   # sync source
DB_B_*   # sync target
```

### 7.3 Convert & Send

```
AUTH_URL=
FEED_ORDER_URL=
SEND_ORDER_USERNAME=
SEND_ORDER_PASSWORD=
```

---

## 8. Struktur Kode Laravel (Usulan)

```
services/armos-tools/
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/LoginController.php
│   │   ├── DashboardController.php
│   │   ├── Menu/*Controller.php          # 14 menu page controllers
│   │   └── Api/*Controller.php           # JSON API controllers
│   ├── Models/ToolsUser.php
│   ├── Services/
│   │   ├── Tms/OrderLocationService.php
│   │   ├── Tms/ReconciliationService.php
│   │   ├── Tms/OrderStatusService.php
│   │   ├── ConvertSend/ExcelToJsonConverter.php
│   │   ├── ConvertSend/OrderFeedClient.php
│   │   ├── Sync/SyncManagerService.php
│   │   ├── Import/LocationImporter.php
│   │   └── Log/LogViewerService.php
│   └── Http/Middleware/...
├── resources/views/
│   ├── layouts/app.blade.php             # AdminLTE
│   ├── layouts/guest.blade.php
│   ├── auth/login.blade.php
│   ├── dashboard.blade.php
│   └── menus/*.blade.php
├── routes/web.php
├── database/migrations/xxxx_create_tools_user_table.php
├── database/seeders/ToolsUserSeeder.php
├── Dockerfile
├── docker-entrypoint.sh
└── .env.example
```

---

## 9. Rencana Rilis Bertahap

### Fase 1 — Foundation (MVP)

1. Scaffold Laravel + Docker service di `services/armos-tools`.
2. Migration `tools_user` + login captcha perkalian + AdminLTE shell.
3. Dashboard + sidebar 14 menu (belum port → Coming Soon).
4. Port penuh:
   - Menu 1 Update Lokasi
   - Menu 11 Check Order Status
   - Menu 6 Convert & Send

### Fase 2 — Operasional Write

Menu 2, 5, 8, 9, 12, 14.

### Fase 3 — Data & Sync

Menu 3, 4, 7, 10, 13 + queue/cron sync & log export.

### Fase 4 — Hardening

Rate limit login, audit log write, dokumentasi ops, matikan Flask v1.

---

## 10. Acceptance Test (Ringkas)

| ID | Skenario | Expected |
|----|----------|----------|
| AT-01 | Login captcha salah | Error + soal baru, tidak masuk |
| AT-02 | Login nama/password salah | Error + soal baru |
| AT-03 | Login benar | Redirect dashboard, sidebar muncul |
| AT-04 | Akses `/menu/...` tanpa login | Redirect login |
| AT-05 | Menu 1: cari faktur → ubah lokasi | `customer_id` ter-update di MAIN |
| AT-06 | Menu 11: cari faktur | Tampil order + detail + product vs inventory |
| AT-07 | Menu 6: upload xlsx valid | JSON tampil; step log hit AUTH + FEED |
| AT-08 | Menu 12: update status di preprod | Hanya DB preprod yang berubah |
| AT-09 | Logout | Session hilang; API mengembalikan redirect/401 ke login |

---

## 11. Risiko & Catatan

| Risiko | Mitigasi |
|--------|----------|
| Write ke PROD (menu 10/12/13/14) | Konfirmasi UI + label env mencolok |
| Sync job panjang | Queue worker + status poll (ganti ThreadPool Flask) |
| Captcha terlalu mudah | Boleh naikkan range angka kemudian; cukup anti-bot ringan |
| Parity Excel convert | Uji dengan file template yang sama seperti v1 |
| Secret di env | Jangan commit `.env`; seed user hanya staging |

---

## 12. Lampiran — Checklist Endpoint (Copy untuk Dev)

```
[ ] GET/POST /login
[ ] GET|POST /logout
[ ] GET /
[ ] GET  /menu/update-lokasi
[ ] GET  /api/orders
[ ] GET  /api/locations
[ ] POST /api/orders/update-location
[ ] GET  /menu/uncheck-reconciliation
[ ] GET  /api/reconciliation
[ ] POST /api/reconciliation/uncheck
[ ] GET  /menu/log-viewer
[ ] GET  /api/log/folders
[ ] GET  /api/log/events
[ ] GET  /api/log/search
[ ] GET  /menu/product-to-route
[ ] GET  /api/product-to-route
[ ] GET  /menu/wms-integrasi
[ ] GET  /api/wms-integration
[ ] POST /api/wms-integration/update
[ ] GET  /menu/convert-send
[ ] POST /api/convert-send          → hits AUTH_URL + FEED_ORDER_URL
[ ] GET  /menu/sync-manager
[ ] POST /api/sync/run
[ ] GET  /api/sync/status
[ ] GET  /api/sync/job/{job_id}
[ ] GET  /menu/update-qty-unloading
[ ] GET  /api/qty-unloading/find
[ ] POST /api/qty-unloading/update
[ ] GET  /menu/hapus-driver-cost
[ ] GET  /api/driver-cost/list
[ ] POST /api/driver-cost/delete
[ ] GET  /menu/import-lokasi
[ ] POST /api/import-lokasi
[ ] GET  /api/import-lokasi/download-log
[ ] GET  /menu/check-order-status
[ ] GET  /api/check-order-status/orders
[ ] GET  /api/check-order-status/order-details
[ ] GET  /api/check-order-status/product-vs-inventory
[ ] GET  /menu/ubah-order-status
[ ] GET  /api/ubah-order-status/search
[ ] POST /api/ubah-order-data/update
[ ] GET  /menu/export-data-csv
[ ] POST /api/export-data-csv/generate
[ ] GET  /api/export-data-csv/download
[ ] GET  /menu/update-order-on-route
[ ] GET  /api/update-order-on-route/search
[ ] POST /api/update-order-on-route/update
```

---

## 13. Referensi

- Kode v1: `app.py`, `templates/*`, `send_orders.py`, `konversi.py`, `sync/*`, `import_lokasi.py`
- Dok teknis v1: [DOKUMENTASI_TEKNIS.md](./DOKUMENTASI_TEKNIS.md)
- Blueprint migrasi panjang: [MIGRASI_LARAVEL11.md](./MIGRASI_LARAVEL11.md) (sebagian fitur table-driven ditunda)

---

*Dokumen ini menjadi sumber kebenaran PRD untuk implementasi ARMoS Tools 2.0. Perubahan scope wajib update versi dokumen ini.*
