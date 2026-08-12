<?php

namespace App\Services\Tms;

use Illuminate\Support\Facades\Storage;

class ExportDataCsvService
{
    public function generate(string $env, string $type): string
    {
        $conn = TmsDatabase::byEnv($env);
        $rows = match ($type) {
            'dataproduct' => $this->exportDataProduct($conn, $env),
            'datavehicle' => TmsDatabase::select($conn, <<<'SQL'
SELECT
  mst_vehicle_id, plate_number, tax_expired, stnk_number, fuel_efficiency_km,
  opening_time, closing_time, status, created_date, created_by, updated_by,
  type, kir_expired, product_restriction_id, region_restriction_id,
  customer_type_restriction_id, specific_customer_restriction_id,
  pickup_location, max_trip_duration, updated_date, driver_id, co_driver_id,
  zona_restriction_id, code, route4me_vehicle_id
FROM mst_vehicle
ORDER BY mst_vehicle_id
SQL),
            'lovconfig' => TmsDatabase::select($conn, 'SELECT lov_id, code, value, status, lov_parent_id FROM mst_list_of_values ORDER BY lov_id'),
            'masterlocation' => TmsDatabase::select($conn, 'SELECT * FROM mst_location_parent ORDER BY mst_location_parent_id'),
            'childlocation' => TmsDatabase::select($conn, <<<'SQL'
SELECT
  mlc.*,
  mvt.name AS vehicle_type_name
FROM mst_location_child mlc
LEFT JOIN mst_vehicle_type mvt
  ON mvt.mst_vehicle_type_id::text = mlc.restriction_type_id
ORDER BY mlc.mst_location_child_id
SQL),
            default => throw new \InvalidArgumentException("Tipe export tidak valid: {$type}"),
        };

        $filename = 'data_'.$type.'_'.now()->format('dmYHis').'.csv';
        $relative = 'data_archive_order/'.$filename;
        Storage::disk('local')->makeDirectory('data_archive_order');

        $handle = fopen(Storage::disk('local')->path($relative), 'w');
        if ($rows) {
            fputcsv($handle, array_keys($rows[0]));
            foreach ($rows as $row) {
                fputcsv($handle, array_map(fn ($v) => is_scalar($v) || $v === null ? $v : json_encode($v), $row));
            }
        } else {
            fputcsv($handle, ['empty']);
        }
        fclose($handle);

        return $filename;
    }

    protected function exportDataProduct(string $conn, string $env): array
    {
        $normalized = in_array(strtolower($env), ['prod', 'production'], true) ? 'prod' : 'preprod';

        if ($normalized === 'preprod') {
            return TmsDatabase::select($conn, <<<'SQL'
SELECT
  mp.mst_product_id, mp.sku, mp.height, mp.width, mp.length, mp.name,
  mp.price, mp.type_product_id, mp.qty, mp.volume, mp.weight, mp.base_uom,
  mp.pack_id, mp.warehouse_id, mp.synced_at, mp.allocated_qty, mp.available_qty,
  mps.expired_date
FROM mst_product mp
LEFT JOIN mst_product_stock mps ON mps.product_id = mp.mst_product_id
ORDER BY mp.mst_product_id
SQL);
        }

        return TmsDatabase::select($conn, <<<'SQL'
SELECT
  mst_product_id, sku, height, width, length, name, price, type_product_id,
  qty, volume, weight, base_uom, pack_id, warehouse_id, synced_at,
  allocated_qty, available_qty, expired_date, batch
FROM mst_product
ORDER BY mst_product_id
SQL);
    }

    public function pathFor(string $filename): string
    {
        $path = Storage::disk('local')->path('data_archive_order/'.$filename);
        if (! is_file($path)) {
            throw new \RuntimeException('File tidak ditemukan: '.$filename);
        }

        return $path;
    }
}
