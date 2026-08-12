<?php

namespace App\Services\Tms;

class CheckOrderStatusService
{
    public function findOrdersByFakturId(string $fakturId): array
    {
        $conn = TmsDatabase::main();
        $sql = <<<'SQL'
SELECT
  o.order_id, o.faktur_id, o.faktur_date, o.delivery_date, o.do_number, o.status,
  o.skip_count, o.created_date,
  u1.username AS created_by,
  o.updated_date,
  u2.username AS updated_by,
  o.notes, o.customer_id, o.warehouse_id, o.delivery_type_id, o.order_integration_id,
  o.origin_name, o.origin_address_1, o.origin_address_2, o.origin_city, o.origin_zipcode,
  o.origin_phone, o.origin_email, o.destination_name, o.destination_address_1,
  o.destination_address_2, o.destination_city, o.destination_zip_code,
  o.destination_phone, o.destination_email, o.client_id, o.cancel_reason,
  o.rdo_integration_id, o.address_change, o.divisi, o.pre_status, o.atena_sorting_code
FROM "order" o
LEFT JOIN keycloak_user u1 ON u1.user_id = o.created_by
LEFT JOIN keycloak_user u2 ON u2.user_id = o.updated_by
WHERE o.faktur_id = ?
ORDER BY o.order_id DESC
SQL;

        return TmsDatabase::select($conn, $sql, [$fakturId]);
    }

    public function findOrderDetailsByOrderId(int $orderId): array
    {
        $conn = TmsDatabase::main();
        $sql = <<<'SQL'
SELECT
  order_detail_id, quantity_faktur, net_price, quantity_wms,
  quantity_delivery, quantity_loading, quantity_unloading, status,
  cancel_reason, notes, order_id, product_id, unit_id, pack_id, line_id,
  unloading_latitude, unloading_longitude, origin_uom, origin_qty,
  total_ctn, total_pcs
FROM order_detail
WHERE order_id = ?
ORDER BY order_detail_id
SQL;

        return TmsDatabase::select($conn, $sql, [$orderId]);
    }

    public function findProductVsInventoryByFakturId(string $fakturId): array
    {
        $conn = TmsDatabase::main();
        $sql = <<<'SQL'
SELECT
  mp.sku,
  od.product_id,
  mp.mst_product_id AS mp_product,
  od.quantity_faktur AS faktur_qty,
  mp.available_qty AS avail_qty,
  CASE
    WHEN mp.available_qty > od.quantity_faktur THEN 'Full Fill'
    ELSE 'Not Full Fill'
  END AS check_status
FROM order_detail od
LEFT JOIN "order" o ON o.order_id = od.order_id
LEFT JOIN mst_product mp ON mp.mst_product_id = od.product_id
WHERE o.faktur_id = ?
ORDER BY od.product_id
SQL;

        return TmsDatabase::select($conn, $sql, [$fakturId]);
    }
}
