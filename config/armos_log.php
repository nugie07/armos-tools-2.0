<?php

return [
    'event_slugs' => [
        ['slug' => 'syncing_inventory', 'label' => '[ARMOS -> WMS] Syncing Inventory'],
        ['slug' => 'synchronizing_order_manifest', 'label' => '[ARMOS -> WMS] Synchronizing Order Manifest'],
        ['slug' => 'synchronizing_route_manifest_generation', 'label' => '[ARMOS -> WMS] Synchronizing Route Manifest Generation'],
        ['slug' => 'patch_order_status_sql', 'label' => '[ARMOS -> SQL] Patch Order Status'],
        ['slug' => 'patch_order_status_atena', 'label' => '[ARMOS -> ATENA] Patch Order Status'],
        ['slug' => 'picklist_route', 'label' => '[ARMOS -> SQL] Picklist Route'],
        ['slug' => 'feed_order_v2_sql_tms', 'label' => '[FEED ORDER V2 SQL -> TMS]'],
        ['slug' => 'feed_order_v2_atena_tms', 'label' => '[FEED ORDER V2 ATENA -> TMS]'],
        ['slug' => 'webhook_good_issue_results', 'label' => 'Webhook Good Issue'],
        ['slug' => 'other', 'label' => 'Lainnya (other)'],
    ],

    'request_config' => [
        'syncing_inventory' => null,
        'synchronizing_order_manifest' => [
            'label' => 'Masukan Do Reference',
            'placeholder' => 'contoh do_reference":"B03SI2505-0558"',
            'search_field' => 'do_reference',
        ],
        'synchronizing_route_manifest_generation' => [
            'label' => 'Masukan Manifest Reference',
            'placeholder' => 'contoh Manifest Reference":"RMSDA0120250508#34"',
            'search_field' => 'manifest_reference',
        ],
        'patch_order_status_sql' => [
            'label' => 'Masukan Faktur Reference',
            'placeholder' => 'contoh Faktur Reference":"M30SI2505-0009"',
            'search_field' => 'faktur_reference',
        ],
        'patch_order_status_atena' => [
            'label' => 'Masukan Route Id',
            'placeholder' => 'contoh Route Id:"RMSDA0120250919#66"',
            'search_field' => 'route_id',
        ],
        'picklist_route' => [
            'label' => 'Masukan Route Id',
            'placeholder' => 'contoh Route Id:"RMSDA0120250919#66"',
            'search_field' => 'header.route_id',
        ],
        'feed_order_v2_sql_tms' => [
            'label' => 'Masukan outbound reference',
            'placeholder' => 'contoh Outbound Reference "C10SI2509-0041"',
            'search_field' => 'outbound_reference',
        ],
        'feed_order_v2_atena_tms' => [
            'label' => 'Masukan outbound reference',
            'placeholder' => 'contoh Outbound Reference "C10SI2509-0041"',
            'search_field' => 'outbound_reference',
        ],
        'webhook_good_issue_results' => [
            'label' => 'Masukan manifest reference',
            'placeholder' => 'contoh manifest_reference":"RMSDA0120251118#196"',
            'search_field' => 'manifest_reference',
        ],
        'other' => [
            'label' => 'Cari Request',
            'placeholder' => 'Masukan Request',
            'search_field' => null,
        ],
    ],
];
