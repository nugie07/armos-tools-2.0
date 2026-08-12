<?php

namespace App\Services\Tms;

class UbahOrderDataService
{
    public const VALID_STATUS = [
        'new', 'loading', 'ready_to_deliver', 'in_delivery', 'completed', 'skip',
        'rejected', 'hold', 'failed', 'return_to_wms', 'inactive', 'in_optimization',
    ];

    public function findByOrderNumber(string $env, string $orderNumber): array
    {
        $conn = TmsDatabase::byEnv($env);
        $sql = <<<'SQL'
SELECT
  order_id, faktur_id, faktur_date, delivery_date, do_number, status,
  skip_count, created_date, created_by, updated_date, updated_by, notes,
  customer_id, warehouse_id, delivery_type_id, order_integration_id,
  origin_name, origin_address_1, origin_address_2, origin_city, origin_zipcode,
  origin_phone, origin_email, destination_name, destination_address_1,
  destination_address_2, destination_city, destination_zip_code,
  destination_phone, destination_email, client_id, cancel_reason,
  rdo_integration_id, address_change, divisi, pre_status, atena_sorting_code
FROM "order"
WHERE do_number = ? OR faktur_id = ?
ORDER BY order_id DESC
SQL;

        return TmsDatabase::select($conn, $sql, [$orderNumber, $orderNumber]);
    }

    public function update(string $env, int $orderId, array $fields, string $updatedBy): int
    {
        $conn = TmsDatabase::byEnv($env);
        $sets = [];
        $bindings = [];

        if (array_key_exists('status', $fields) && $fields['status'] !== null && $fields['status'] !== '') {
            $sets[] = 'status = ?';
            $bindings[] = $fields['status'];
        }
        if (array_key_exists('order_integration_id', $fields) && $fields['order_integration_id'] !== null && $fields['order_integration_id'] !== '') {
            $sets[] = 'order_integration_id = ?';
            $bindings[] = $fields['order_integration_id'];
        }
        if (array_key_exists('delivery_date', $fields) && $fields['delivery_date'] !== null && $fields['delivery_date'] !== '') {
            $sets[] = 'delivery_date = ?';
            $bindings[] = $fields['delivery_date'];
        }

        $sets[] = 'updated_date = CURRENT_TIMESTAMP';
        $sets[] = 'updated_by = ?';
        $bindings[] = $updatedBy;
        $bindings[] = $orderId;

        if (count($sets) <= 2 && ! array_key_exists('status', $fields) && ! array_key_exists('order_integration_id', $fields) && ! array_key_exists('delivery_date', $fields)) {
            // still allow audit-only update
        }

        $sql = 'UPDATE "order" SET '.implode(', ', $sets).' WHERE order_id = ?';

        return TmsDatabase::affectingStatement($conn, $sql, $bindings);
    }
}
