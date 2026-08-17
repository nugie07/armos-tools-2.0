@extends('layouts.app')

@section('title', $menu['title'])
@section('page_title', $menu['title'])
@section('breadcrumb', $menu['title'])

@section('content')
<div class="card card-outline card-primary">
  <div class="card-header">
    <h3 class="card-title"><i class="{{ $menu['icon'] }} mr-2"></i>{{ $menu['title'] }}</h3>
    <div class="card-tools">
      <span class="badge badge-warning">Coming Soon · UI</span>
      <span class="badge badge-info">API Stub Ready</span>
    </div>
  </div>
  <div class="card-body">
    <p>{{ $menu['description'] }}</p>
    <hr>
    <p class="mb-2"><strong>Endpoint terkait (lihat Postman collection):</strong></p>
    <ul class="mb-0">
      @php
        $endpoints = [
          'update-lokasi' => ['GET /api/orders', 'GET /api/locations', 'POST /api/orders/update-location'],
          'uncheck-reconciliation' => ['GET /api/reconciliation', 'POST /api/reconciliation/uncheck'],
          'log-viewer' => ['GET /api/logs', 'GET /api/logs/{id}', 'POST /api/logs/sync', 'GET /api/logs/sync-status'],
          'product-to-route' => ['GET /api/product-to-route'],
          'wms-integrasi' => ['GET /api/wms-integration', 'POST /api/wms-integration/update'],
          'convert-send' => ['POST /api/convert-send'],
          'sync-manager' => ['POST /api/sync/run', 'GET /api/sync/job/{id}', 'GET /api/sync/status'],
          'update-qty-unloading' => ['GET /api/qty-unloading/find', 'POST /api/qty-unloading/update'],
          'hapus-driver-cost' => ['GET /api/driver-cost/list', 'POST /api/driver-cost/delete'],
          'import-lokasi' => ['POST /api/import-lokasi', 'GET /api/import-lokasi/download-log'],
          'check-order-status' => ['GET /api/check-order-status/orders', 'GET /api/check-order-status/order-details', 'GET /api/check-order-status/product-vs-inventory'],
          'ubah-order-status' => ['GET /api/ubah-order-status/search', 'POST /api/ubah-order-data/update'],
          'export-data-csv' => ['POST /api/export-data-csv/generate', 'GET /api/export-data-csv/download'],
          'update-order-on-route' => ['GET /api/update-order-on-route/search', 'POST /api/update-order-on-route/update'],
        ];
      @endphp
      @foreach($endpoints[$menu['key']] ?? [] as $ep)
        <li><code>{{ $ep }}</code></li>
      @endforeach
    </ul>
  </div>
</div>
@endsection
