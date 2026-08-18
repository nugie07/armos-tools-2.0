# Log Viewer (Monitoring Index)

## Ringkasan arsitektur

- **Listing/search** → `monitoring.api_request_log_viewer` (app DB `armos_tools`)
- **Detail request/response** → TMS `sys_api_request_log` by `api_request_log_id` only
- **Sync** → incremental checkpoint (`last_synced_created_date` + `last_synced_id`), batch 5000, **hanya 14 hari terakhir**
- **Schedule** → setiap 3 jam (prod + preprod)
- **Manual sync** → `POST /api/logs/sync`, cooldown 1 jam (backend enforced)

### Adaptasi vs prompt

| Prompt | Implementasi project |
|--------|----------------------|
| `production.api_request_log` | TMS table `sys_api_request_log` (Flask legacy) via `tms_prod` / `tms_preprod` |
| Single production DB | Dual env (session navbar) → monitoring rows + sync state keyed by `environment` |
| Schema `monitoring` | Created on **app** Postgres (`DB_*` / `armos_tools`) |

## Environment variables

```env
LOG_SYNC_ENABLED=true
LOG_SYNC_BATCH_SIZE=5000
LOG_SYNC_MANUAL_COOLDOWN_MINUTES=60
LOG_SYNC_SCHEDULE_HOURS=3
LOG_SYNC_LOOKBACK_DAYS=14
LOG_SYNC_INITIAL_FROM=          # optional extra lower bound (later than lookback wins)
LOG_SYNC_STALE_RUNNING_MINUTES=70
LOG_SYNC_PRODUCTION_TABLE=sys_api_request_log
LOG_SYNC_ADVISORY_LOCK_KEY=8142026
```

Queue: `QUEUE_CONNECTION=database` (sudah default di `.env.example`).

## Migration

```bash
php artisan migrate
```

Membuat:

- `monitoring.api_request_log_viewer`
- `monitoring.log_sync_state` (seed row `prod` + `preprod`)

Tidak mengubah TMS/production table.

## Scheduler & Queue (wajib di server)

1. Cron:

```cron
* * * * * cd /var/www && php artisan schedule:run >> /dev/null 2>&1
```

2. Queue worker:

```bash
php artisan queue:work database --sleep=1 --tries=1 --timeout=3600
```

(atau supervisor/systemd). Tanpa worker, manual sync hanya dispatch job dan tidak jalan.

## Initial sync

Setelah migrate + Env Configuration TMS terisi:

```bash
# Dry-run (tidak tulis monitoring)
php artisan logs:sync --env=prod --dry-run --batch-size=1000

# Sync inline
php artisan logs:sync --env=prod --batch-size=5000

# Atau via queue
php artisan logs:sync --env=prod --queue
```

Default hanya log **14 hari terakhir**. `--from=` hanya mempersempit window (tanggal lebih baru dari lookback).

Ulangi untuk `--env=preprod` bila perlu.

## API

| Method | Path | Keterangan |
|--------|------|------------|
| GET | `/api/logs/events` | Catalog event + search field |
| GET | `/api/logs` | List/search monitoring (page) |
| GET | `/api/logs/{id}` | Detail 1 row dari TMS |
| GET | `/api/logs/sync-status` | Status + cooldown |
| POST | `/api/logs/sync` | Trigger manual sync job |

Query list: `event_slug`, `reference_value` (exact), `date_from`, `date_to` (calendar day of `created_date`, inclusive), `page`, `per_page` (default 15).

Auth: session login + middleware `armos.env` (wajib pilih Production / Pre Production di navbar).

## Production DB permission

Ideal: user koneksi `DATABASE_PROD_*` / `DATABASE_PREPROD_*` untuk Log Viewer hanya **SELECT** pada `sys_api_request_log`.

Jangan beri INSERT/UPDATE/DELETE pada table log production ke aplikasi monitoring.

Index production (opsional, hati-hati di live):

```sql
CREATE INDEX CONCURRENTLY idx_api_request_log_sync
ON sys_api_request_log (created_date, api_request_log_id);
```

Cek dulu index existing sebelum membuat.

## Troubleshooting

| Gejala | Cek |
|--------|-----|
| Listing kosong | Sudah `logs:sync`? Env navbar cocok dengan yang di-sync? |
| Manual sync 429 | Cooldown 60 menit; lihat `next_manual_sync_at` |
| Manual sync 409 | Sync masih `running` |
| Sync job tidak jalan | `queue:work` hidup? `jobs` table ada? |
| Detail 404 | ID ada di monitoring tapi hilang di TMS, atau env salah |
| Auth/env 400 | Pilih environment di navbar |

## UI

Menu **Log Viewer**: pilih event, optional reference exact + date range, Search, double-click baris untuk detail, tombol **Sync Now** + status cooldown.
