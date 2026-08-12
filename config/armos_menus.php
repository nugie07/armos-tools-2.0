<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ARMoS Tools — Sidebar / Dashboard menus (PRD 2.0)
    |--------------------------------------------------------------------------
    */
    'items' => [
        [
            'key' => 'update-lokasi',
            'title' => 'Update Lokasi Customer',
            'route' => 'menu.update-lokasi',
            'path' => '/menu/update-lokasi',
            'icon' => 'fas fa-map-marker-alt',
            'description' => 'Cari order by faktur/warehouse lalu ubah lokasi customer (mst_location_child).',
        ],
        [
            'key' => 'uncheck-reconciliation',
            'title' => 'Uncheck Reconciliation',
            'route' => 'menu.uncheck-reconciliation',
            'path' => '/menu/uncheck-reconciliation',
            'icon' => 'fas fa-unlink',
            'description' => 'Cari dan hapus baris order_document_reconciliation berdasarkan order.',
        ],
        [
            'key' => 'log-viewer',
            'title' => 'Log Viewer',
            'route' => 'menu.log-viewer',
            'path' => '/menu/log-viewer',
            'icon' => 'fas fa-file-alt',
            'description' => 'Browse log API lokal dari SQLite data_log/{tanggal}/{event}.db.',
        ],
        [
            'key' => 'product-to-route',
            'title' => 'Product To Route',
            'route' => 'menu.product-to-route',
            'path' => '/menu/product-to-route',
            'icon' => 'fas fa-route',
            'description' => 'Cari relasi produk/SKU ke route berdasarkan rentang tanggal (read-only MAIN).',
        ],
        [
            'key' => 'wms-integrasi',
            'title' => 'Update WMS Integrasi',
            'route' => 'menu.wms-integrasi',
            'path' => '/menu/wms-integrasi',
            'icon' => 'fas fa-exchange-alt',
            'description' => 'Update order_integration_id pada tabel order.',
        ],
        [
            'key' => 'convert-send',
            'title' => 'Convert & Send',
            'route' => 'menu.convert-send',
            'path' => '/menu/convert-send',
            'icon' => 'fas fa-file-excel',
            'description' => 'Upload Excel preprod → convert JSON → auth + feed order ke API eksternal.',
        ],
        [
            'key' => 'sync-manager',
            'title' => 'Sync Manager',
            'route' => 'menu.sync-manager',
            'path' => '/menu/sync-manager',
            'icon' => 'fas fa-sync',
            'description' => 'ETL fact_order / fact_delivery memakai DB Production / Pre Production sesuai session navbar.',
        ],
        [
            'key' => 'update-qty-unloading',
            'title' => 'Update Qty Unloading',
            'route' => 'menu.update-qty-unloading',
            'path' => '/menu/update-qty-unloading',
            'icon' => 'fas fa-boxes',
            'description' => 'Update quantity_unloading pada order_detail.',
        ],
        [
            'key' => 'hapus-driver-cost',
            'title' => 'Hapus Driver Cost',
            'route' => 'menu.hapus-driver-cost',
            'path' => '/menu/hapus-driver-cost',
            'icon' => 'fas fa-trash',
            'description' => 'List dan hapus order_cost berdasarkan manifest reference.',
        ],
        [
            'key' => 'import-lokasi',
            'title' => 'Import Lokasi',
            'route' => 'menu.import-lokasi',
            'path' => '/menu/import-lokasi',
            'icon' => 'fas fa-upload',
            'description' => 'Import master lokasi parent/child ke PREPROD atau PROD dari Excel.',
        ],
        [
            'key' => 'check-order-status',
            'title' => 'Check Order Status',
            'route' => 'menu.check-order-status',
            'path' => '/menu/check-order-status',
            'icon' => 'fas fa-search',
            'description' => 'Lihat list order, detail, dan product vs inventory (read-only MAIN).',
        ],
        [
            'key' => 'ubah-order-status',
            'title' => 'Ubah Order Data',
            'route' => 'menu.ubah-order-status',
            'path' => '/menu/ubah-order-status',
            'icon' => 'fas fa-edit',
            'description' => 'Ubah status / integration id / delivery_date order di PREPROD atau PROD.',
        ],
        [
            'key' => 'export-data-csv',
            'title' => 'Export Data CSV',
            'route' => 'menu.export-data-csv',
            'path' => '/menu/export-data-csv',
            'icon' => 'fas fa-file-csv',
            'description' => 'Generate & download CSV master (product, vehicle, LOV, lokasi).',
        ],
        [
            'key' => 'update-order-on-route',
            'title' => 'Update Order On Route',
            'route' => 'menu.update-order-on-route',
            'path' => '/menu/update-order-on-route',
            'icon' => 'fas fa-truck',
            'description' => 'Update status / integration id pada tabel route by manifest.',
        ],
    ],
];
