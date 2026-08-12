<?php

namespace App\Services\Tms;

class UpdateOrderOnRouteService
{
    public const VALID_STATUS = [
        'new', 'loading', 'ready_to_deliver', 'in_delivery',
        'delivery_success', 'delivery_completed', 'rejected',
    ];

    public function findByManifest(string $env, string $manifestReference): array
    {
        $conn = TmsDatabase::byEnv($env);
        $sql = <<<'SQL'
SELECT
  od.order_id,
  od.faktur_id,
  od.status AS order_status,
  ro.status AS route_status,
  ro.manifest_reference,
  ro.manifest_integration_id
FROM "order" od
LEFT JOIN route_detail rd ON rd.order_id = od.order_id
LEFT JOIN route ro ON ro.route_id = rd.route_id
WHERE ro.manifest_reference = ?
ORDER BY od.order_id
SQL;

        return TmsDatabase::select($conn, $sql, [$manifestReference]);
    }

    public function updateRoute(string $env, string $manifestReference, ?string $status, ?string $manifestIntegrationId): int
    {
        $conn = TmsDatabase::byEnv($env);
        $sets = [];
        $bindings = [];

        if ($status !== null && $status !== '') {
            $sets[] = 'status = ?';
            $bindings[] = $status;
        }
        if ($manifestIntegrationId !== null && $manifestIntegrationId !== '') {
            $sets[] = 'manifest_integration_id = ?';
            $bindings[] = $manifestIntegrationId;
        }

        if (! $sets) {
            throw new \InvalidArgumentException('Minimal satu field (status atau manifest_integration_id) harus diisi.');
        }

        $bindings[] = $manifestReference;
        $sql = 'UPDATE route SET '.implode(', ', $sets).' WHERE manifest_reference = ?';

        return TmsDatabase::affectingStatement($conn, $sql, $bindings);
    }
}
