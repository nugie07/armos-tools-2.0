<?php

namespace App\Services\Tms;

class ReconciliationService
{
    public function fetchByFaktur(string $fakturId): array
    {
        $conn = TmsDatabase::main();
        $sql = <<<'SQL'
SELECT odr.*, od.faktur_id
FROM order_document_reconciliation odr
LEFT JOIN "order" od ON od.order_id = odr.order_id
WHERE od.faktur_id = ?
SQL;

        return TmsDatabase::select($conn, $sql, [$fakturId]);
    }

    public function deleteByOrderId(int $orderId): int
    {
        $conn = TmsDatabase::main();
        $sql = 'DELETE FROM order_document_reconciliation WHERE order_id = ?';

        return TmsDatabase::affectingStatement($conn, $sql, [$orderId]);
    }
}
