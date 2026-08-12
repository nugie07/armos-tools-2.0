<?php

namespace App\Services\Tms;

class WmsIntegrationService
{
    public function fetchByFaktur(string $fakturId): array
    {
        $conn = TmsDatabase::main();
        $sql = <<<'SQL'
SELECT odr.order_id, odr.faktur_id, odr.faktur_date, odr.status, odr.order_integration_id
FROM "order" odr
WHERE odr.faktur_id = ?
SQL;

        return TmsDatabase::select($conn, $sql, [$fakturId]);
    }

    public function update(int $orderId, string $orderIntegrationId): int
    {
        $conn = TmsDatabase::main();
        $sql = 'UPDATE "order" SET order_integration_id = ? WHERE order_id = ?';

        return TmsDatabase::affectingStatement($conn, $sql, [$orderIntegrationId, $orderId]);
    }
}
