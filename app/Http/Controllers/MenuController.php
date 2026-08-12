<?php

namespace App\Http\Controllers;

use App\Services\Tms\OrderLocationService;
use App\Services\Tms\QtyUnloadingService;
use App\Support\ArmosEnvironment;
use Throwable;

class MenuController extends Controller
{
    public function show(string $key)
    {
        $menu = collect(config('armos_menus.items', []))
            ->firstWhere('key', $key);

        abort_if(! $menu, 404);

        return match ($key) {
            'update-lokasi' => $this->updateLokasi($menu),
            'uncheck-reconciliation' => view('menus.uncheck_recon', compact('menu')),
            'log-viewer' => view('menus.log_viewer', compact('menu')),
            'product-to-route' => view('menus.product_to_route', compact('menu')),
            'wms-integrasi' => view('menus.wms_integrasi', compact('menu')),
            'convert-send' => view('menus.convert_send', compact('menu')),
            'sync-manager' => view('menus.sync_manager', compact('menu')),
            'update-qty-unloading' => $this->updateQtyUnloading($menu),
            'hapus-driver-cost' => view('menus.hapus_driver_cost', compact('menu')),
            'import-lokasi' => view('menus.import_lokasi', compact('menu')),
            'check-order-status' => view('menus.check_order_status', compact('menu')),
            'ubah-order-status' => view('menus.ubah_order_status', compact('menu')),
            'export-data-csv' => view('menus.export_data_csv', compact('menu')),
            'update-order-on-route' => view('menus.update_order_on_route', compact('menu')),
            default => view('menus.show', compact('menu')),
        };
    }

    protected function updateLokasi(array $menu)
    {
        return $this->withWarehouses($menu, 'menus.update_lokasi', OrderLocationService::class);
    }

    protected function updateQtyUnloading(array $menu)
    {
        return $this->withWarehouses($menu, 'menus.update_qty_unloading', QtyUnloadingService::class);
    }

    /**
     * @param  class-string  $serviceClass
     */
    protected function withWarehouses(array $menu, string $view, string $serviceClass)
    {
        $warehouses = [];
        $warehouseError = null;

        if (! ArmosEnvironment::hasSelection()) {
            $warehouseError = 'PILIH ENVIRONMENT TERLEBIH DAHULU (dropdown kanan atas).';

            return view($view, compact('menu', 'warehouses', 'warehouseError'));
        }

        try {
            $rows = app($serviceClass)->fetchWarehouses();
            foreach ($rows as $row) {
                $warehouses[] = [
                    'id' => $row['mst_location_child_id'] ?? null,
                    'name' => $row['name'] ?? '',
                ];
            }
        } catch (Throwable $e) {
            $warehouseError = $e->getMessage();
        }

        return view($view, compact('menu', 'warehouses', 'warehouseError'));
    }
}
