<?php

namespace App\Services\Tms;

class ProductToRouteService
{
    public function fetch(string $sku, string $startDate, string $endDate): array
    {
        $conn = TmsDatabase::main();
        $sql = <<<'SQL'
SELECT ro.route_id, ro.manifest_reference, ro.status AS route_status,
       o.faktur_id, o.status AS order_status, od.quantity_faktur, o.faktur_date
FROM route ro
LEFT JOIN route_detail rd ON rd.route_id = ro.route_id
LEFT JOIN "order" o ON o.order_id = rd.order_id
LEFT JOIN order_detail od ON od.order_id = o.order_id
LEFT JOIN mst_product mp ON mp.mst_product_id = od.product_id
WHERE mp.sku = ?
  AND o.faktur_date BETWEEN ?::date AND ?::date
ORDER BY o.faktur_date
SQL;

        return TmsDatabase::select($conn, $sql, [$sku, $startDate, $endDate]);
    }
}
