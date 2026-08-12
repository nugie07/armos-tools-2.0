<?php

namespace App\Services\Tms;

class OrderLocationService
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

    public function fetchOrdersByFakturAndWarehouse(string $fakturId, int $warehouseId): array
    {
        $conn = TmsDatabase::main();
        $sql = <<<'SQL'
SELECT od.faktur_date, od.faktur_id, od.order_id, od.warehouse_id,
       mlc.mst_location_child_id, mlc.code, mlc.name
FROM "order" od
LEFT JOIN mst_location_child mlc
  ON od.customer_id = mlc.mst_location_child_id
WHERE od.faktur_id = ? AND od.warehouse_id = ?
SQL;

        return TmsDatabase::select($conn, $sql, [$fakturId, $warehouseId]);
    }

    public function fetchAllLocations(): array
    {
        $conn = TmsDatabase::main();
        $sql = 'SELECT mlc.mst_location_child_id, mlc.code, mlc.name FROM mst_location_child mlc';

        return TmsDatabase::select($conn, $sql);
    }

    public function updateOrderCustomerLocation(string $fakturId, int $customerId): int
    {
        $conn = TmsDatabase::main();
        $sql = 'UPDATE "order" SET customer_id = ? WHERE faktur_id = ?';

        return TmsDatabase::affectingStatement($conn, $sql, [$customerId, $fakturId]);
    }
}
