<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Log Viewer / Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Production source: TMS Postgres table sys_api_request_log (read-only).
    | Monitoring index: app DB schema monitoring.*
    |
    */

    'enabled' => (bool) env('LOG_SYNC_ENABLED', true),

    'batch_size' => (int) env('LOG_SYNC_BATCH_SIZE', 5000),

    'manual_cooldown_minutes' => (int) env('LOG_SYNC_MANUAL_COOLDOWN_MINUTES', 60),

    'schedule_hours' => (int) env('LOG_SYNC_SCHEDULE_HOURS', 3),

    /**
     * Only sync / keep monitoring rows within this many days.
     */
    'lookback_days' => (int) env('LOG_SYNC_LOOKBACK_DAYS', 14),

    /**
     * Optional extra lower bound (Y-m-d). Combined with lookback (the later date wins).
     */
    'initial_from' => env('LOG_SYNC_INITIAL_FROM'),

    /**
     * If status stays "running" longer than this, treat the job as dead and allow retry.
     */
    'stale_running_minutes' => (int) env('LOG_SYNC_STALE_RUNNING_MINUTES', 70),

    'production_table' => env('LOG_SYNC_PRODUCTION_TABLE', 'sys_api_request_log'),

    'advisory_lock_key' => (int) env('LOG_SYNC_ADVISORY_LOCK_KEY', 8142026),

    /*
    |--------------------------------------------------------------------------
    | Event catalog (slug → label + search field + matcher)
    |--------------------------------------------------------------------------
    |
    | matcher: how production.event text maps to this slug (order matters;
    | first match wins). Prefix matchers use str_starts_with.
    | Contains matchers require all needles to appear in the event string.
    |
    */

    'events' => [
        'syncing_inventory' => [
            'label' => '[ARMOS -> WMS] Syncing Inventory',
            'search_field' => null,
            'search_label' => null,
            'placeholder' => null,
            'match' => ['equals' => '[ARMOS -> WMS] Syncing Inventory'],
        ],
        'synchronizing_order_manifest' => [
            'label' => '[ARMOS -> WMS] Synchronizing Order Manifest',
            'search_field' => 'do_reference',
            'search_label' => 'Masukan Do Reference',
            'placeholder' => 'contoh do_reference":"B03SI2505-0558"',
            'match' => [
                'contains' => ['[ARMOS -> WMS] Synchronizing Order', 'Manifest'],
                'not_contains' => ['Manifest Generation'],
            ],
        ],
        'synchronizing_route_manifest_generation' => [
            'label' => '[ARMOS -> WMS] Synchronizing Route Manifest Generation',
            'search_field' => 'manifest_reference',
            'search_label' => 'Masukan Manifest Reference',
            'placeholder' => 'contoh Manifest Reference":"RMSDA0120250508#34"',
            'match' => [
                'contains' => ['[ARMOS -> WMS] Synchronizing Route', 'Manifest Generation'],
            ],
        ],
        'patch_order_status_sql' => [
            'label' => '[ARMOS -> SQL] Patch Order Status',
            'search_field' => 'faktur_reference_id',
            'search_label' => 'Masukan Faktur Reference',
            'placeholder' => 'contoh Faktur Reference":"M30SI2505-0009"',
            'match' => ['starts_with' => '[ARMOS -> SQL] Patch Order Status'],
        ],
        'patch_order_status_atena' => [
            'label' => '[ARMOS -> ATENA] Patch Order Status',
            'search_field' => 'route_id',
            'search_label' => 'Masukan Route Id',
            'placeholder' => 'contoh Route Id:"RMSDA0120250919#66"',
            'match' => ['starts_with' => '[ARMOS -> ATENA] Patch Order Status'],
        ],
        'picklist_route' => [
            'label' => '[ARMOS -> SQL] Picklist Route',
            'search_field' => 'header.route_id',
            'search_label' => 'Masukan Route Id',
            'placeholder' => 'contoh Route Id:"RMSDA0120250919#66"',
            'match' => ['starts_with' => '[ARMOS -> SQL] Picklist Route'],
        ],
        'feed_order_v2_sql_tms' => [
            'label' => '[FEED ORDER V2 SQL -> TMS]',
            'search_field' => 'outbound_reference',
            'search_label' => 'Masukan outbound reference',
            'placeholder' => 'contoh Outbound Reference "C10SI2509-0041"',
            'match' => ['equals' => '[FEED ORDER V2 SQL -> TMS]'],
        ],
        'feed_order_v2_atena_tms' => [
            'label' => '[FEED ORDER V2 ATENA -> TMS]',
            'search_field' => 'outbound_reference',
            'search_label' => 'Masukan outbound reference',
            'placeholder' => 'contoh Outbound Reference "C10SI2509-0041"',
            'match' => ['equals' => '[FEED ORDER V2 ATENA -> TMS]'],
        ],
        'webhook_good_issue_results' => [
            'label' => 'Webhook Good Issue',
            'search_field' => 'manifest_reference',
            'search_label' => 'Masukan manifest reference',
            'placeholder' => 'contoh manifest_reference":"RMSDA0120251118#196"',
            'match' => ['equals' => '[WMS -> ARMOS] WEBHOOK_GOOD_ISSUE_RESULTS'],
        ],
        'other' => [
            'label' => 'Lainnya (other)',
            'search_field' => null,
            'search_label' => null,
            'placeholder' => null,
            'match' => null,
        ],
    ],
];
