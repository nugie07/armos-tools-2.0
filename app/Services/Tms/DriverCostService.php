<?php

namespace App\Services\Tms;

class DriverCostService
{
    public function list(string $manifestReference, int $page = 1, int $perPage = 20): array
    {
        $conn = TmsDatabase::main();
        $offset = max(0, ($page - 1) * $perPage);

        $sql = <<<'SQL'
SELECT oc.order_cost_id, rt.manifest_reference, oc.nominal,
       dd.driver_name, oc.receipt_picture
FROM order_cost oc
LEFT JOIN route_detail rd ON rd.order_id = oc.order_id
LEFT JOIN route rt ON rt.route_id = rd.route_id
LEFT JOIN dma_driver dd ON dd.driver_id = oc."driverIdDriverId"
WHERE rt.manifest_reference = ?
ORDER BY oc.order_cost_id DESC
LIMIT ? OFFSET ?
SQL;

        $countSql = <<<'SQL'
SELECT COUNT(1) AS total
FROM order_cost oc
LEFT JOIN route_detail rd ON rd.order_id = oc.order_id
LEFT JOIN route rt ON rt.route_id = rd.route_id
WHERE rt.manifest_reference = ?
SQL;

        $rows = TmsDatabase::select($conn, $sql, [$manifestReference, $perPage, $offset]);
        $total = (int) (TmsDatabase::select($conn, $countSql, [$manifestReference])[0]['total'] ?? 0);

        return [
            'rows' => $rows,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ];
    }

    public function delete(int $orderCostId): int
    {
        $conn = TmsDatabase::main();
        $sql = 'DELETE FROM order_cost WHERE order_cost_id = ?';

        return TmsDatabase::affectingStatement($conn, $sql, [$orderCostId]);
    }
}
