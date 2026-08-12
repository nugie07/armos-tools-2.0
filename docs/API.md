# ARMoS Tools 2.0 — Dokumentasi API

Sumber SQL & fungsi: `query_function.md`.

## Postman

- `docs/postman/ARMoS_Tools_2.0_API.postman_collection.json`
- `docs/postman/ARMoS_Tools_Local.postman_environment.json`

Base URL: `http://amors-tools.nuradigital.test`  
Auth: session cookie setelah login (`nama` + password + captcha).  
CSRF: header `X-CSRF-TOKEN` untuk POST.

## Kontrak response

```json
{ "status": 200, "message": null, "data": [] }
```

Write ops juga mengembalikan `affected` bila relevan.

## Koneksi DB (mengikuti query_function.md)

| Label | Resolusi | Menu |
|-------|----------|------|
| MAIN | `DATABASE_MAIN_*` atau fallback env navbar (PROD/PREPROD) | 1–5, 8–9, 11 |
| PREPROD/PROD | `DATABASE_PREPROD_*` / `DATABASE_PROD_*` | 10, 12–14 |
| DB A → DB B | `DB_A_*` → `DB_B_*` | 7 Sync |
| SQLite | `storage/app/data_log/{DDMMYYYY}/{slug}.db` | 3 Log Viewer |

Isi kredensial via **Env Configuration**.

## Endpoint per menu

| # | Fitur | Endpoint | Query write utama |
|---|-------|----------|-------------------|
| 1 | Update Lokasi | `GET /api/warehouses`, `GET /api/orders`, `GET /api/locations`, `POST /api/orders/update-location` | `UPDATE "order" SET customer_id` |
| 2 | Uncheck Recon | `GET /api/reconciliation`, `POST /api/reconciliation/uncheck` | `DELETE order_document_reconciliation` |
| 3 | Log Viewer | `GET /api/log/folders\|events\|search` | — (SQLite read) |
| 4 | Product To Route | `GET /api/product-to-route` | — |
| 5 | WMS Integrasi | `GET /api/wms-integration`, `POST .../update` | `UPDATE order.order_integration_id` |
| 6 | Convert & Send | `POST /api/convert-send` | — (AUTH_URL + FEED_ORDER_URL) |
| 7 | Sync Manager | `POST /api/sync/run`, `GET /api/sync/job/{id}`, `GET /api/sync/status` | `tms_sync_log` (+ fact_* menyusul) |
| 8 | Qty Unloading | `GET /api/qty-unloading/warehouses\|find`, `POST .../update` | `UPDATE order_detail.quantity_unloading` |
| 9 | Driver Cost | `GET /api/driver-cost/list`, `POST .../delete` | `DELETE order_cost` |
| 10 | Import Lokasi | `POST /api/import-lokasi`, `GET .../download-log` | `INSERT mst_location_*` |
| 11 | Check Order | `GET /api/check-order-status/*` | — |
| 12 | Ubah Order | `GET .../search`, `POST /api/ubah-order-data/update` | `UPDATE "order"` |
| 13 | Export CSV | `POST .../generate`, `GET .../download` | — |
| 14 | Order On Route | `GET .../search`, `POST .../update` | `UPDATE route` |

## Struktur kode

```
app/Services/Tms/*Service.php   # SQL dari query_function.md
app/Services/Log/LogViewerService.php
app/Services/ConvertSend/ConvertSendService.php
app/Services/Sync/SyncManagerService.php
app/Services/Import/LocationImporter.php
app/Http/Controllers/Api/*Controller.php
```
