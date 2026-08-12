<?php

namespace App\Services\Tms;

class QtyUnloadingService
{
    public function fetchWarehouses(): array
    {
        $conn = TmsDatabase::main();
        $sql = <<<'SQL'
SELECT mlc.mst_location_child_id, mlc.name
FROM mst_location_child mlc
LEFT JOIN mst_location_parent mlp
  ON mlc.mst_location_parent_id = mlp.mst_location_parent_id
WHERE mlp.type_id = ?
SQL;

        return TmsDatabase::select($conn, $sql, [TmsDatabase::whType()]);
    }

    public function find(int|string $warehouseId, string $fakturId, string $sku): array
    {
        $conn = TmsDatabase::main();
        $sql = <<<'SQL'
SELECT od.order_detail_id, mp.sku, od.quantity_faktur, od.quantity_unloading
FROM order_detail od
LEFT JOIN "order" o ON o.order_id = od.order_id
LEFT JOIN mst_product mp ON mp.mst_product_id = od.product_id
WHERE o.warehouse_id = ?
  AND o.faktur_id = ?
  AND mp.sku = ?
SQL;

        return TmsDatabase::select($conn, $sql, [$warehouseId, $fakturId, $sku]);
    }

    public function update(int $orderDetailId, float|int|string $qty): int
    {
        $conn = TmsDatabase::main();
        $sql = 'UPDATE order_detail SET quantity_unloading = ? WHERE order_detail_id = ?';

        return TmsDatabase::affectingStatement($conn, $sql, [$qty, $orderDetailId]);
    }
}
