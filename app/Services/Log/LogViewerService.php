<?php

namespace App\Services\Log;

use Illuminate\Support\Facades\File;
use PDO;

class LogViewerService
{
    public function baseDir(): string
    {
        $dir = storage_path('app/data_log');
        File::ensureDirectoryExists($dir);

        return $dir;
    }

    /**
     * @return list<string> folder names DDMMYYYY
     */
    public function folders(): array
    {
        return collect(File::directories($this->baseDir()))
            ->map(fn ($d) => basename($d))
            ->filter(fn ($name) => preg_match('/^\d{8}$/', $name))
            ->sortDesc()
            ->values()
            ->all();
    }

    public function events(): array
    {
        $config = config('armos_log.request_config', []);

        return array_map(function ($item) use ($config) {
            $cfg = $config[$item['slug']] ?? null;

            return [
                'slug' => $item['slug'],
                'label' => $item['label'],
                'request_config' => $cfg === null ? null : [
                    'label' => $cfg['label'] ?? 'Cari Request',
                    'placeholder' => $cfg['placeholder'] ?? 'Masukan Request',
                    'search_field' => $cfg['search_field'] ?? null,
                ],
            ];
        }, config('armos_log.event_slugs', []));
    }

    /**
     * Response shape compatible with Flask log_viewer.html:
     * data (rows), page, per_page, pages, has_more
     */
    public function search(
        string $folder,
        string $event,
        ?string $requestFilter = null,
        ?string $searchField = null,
        int $page = 1,
        int $perPage = 15,
    ): array {
        $page = max(1, $page);
        $perPage = max(1, $perPage);

        if ($folder === '' || $event === '') {
            throw new \InvalidArgumentException('folder dan event wajib dipilih');
        }
        if (! preg_match('/^\d{8}$/', $folder)) {
            throw new \InvalidArgumentException('invalid folder (harus DDMMYYYY)');
        }

        $valid = collect(config('armos_log.event_slugs'))->pluck('slug')->all();
        if (! in_array($event, $valid, true)) {
            throw new \InvalidArgumentException('invalid event');
        }

        $dbPath = $this->baseDir().DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$event.'.db';
        $baseReal = realpath($this->baseDir());
        if (! is_file($dbPath)) {
            return [
                'data' => [],
                'page' => $page,
                'per_page' => $perPage,
                'pages' => 0,
                'has_more' => false,
            ];
        }

        $resolved = realpath($dbPath);
        if ($baseReal === false || $resolved === false || ! str_starts_with($resolved, $baseReal)) {
            throw new \InvalidArgumentException('invalid path');
        }

        $pdo = new PDO('sqlite:'.$resolved);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $where = '1=1';
        $bindings = [];
        $q = trim((string) $requestFilter);
        $field = trim((string) $searchField);

        if ($q !== '' && $field !== '') {
            $where = "LOWER(CAST(json_extract(request, ?) AS TEXT)) LIKE LOWER('%' || ? || '%')";
            $bindings[] = '$.'.$field;
            $bindings[] = $q;
        } elseif ($q !== '') {
            $where = "LOWER(CAST(request AS TEXT)) LIKE LOWER('%' || ? || '%')";
            $bindings[] = $q;
        }

        $limitFetch = $perPage + 1;
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT api_request_log_id, event, request, response, created_date
FROM log
WHERE {$where}
ORDER BY created_date DESC
LIMIT ? OFFSET ?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([...$bindings, $limitFetch, $offset]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hasMore = count($rows) > $perPage;
        if ($hasMore) {
            $rows = array_slice($rows, 0, $perPage);
        }

        return [
            'data' => $rows,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => $hasMore ? $page + 1 : $page,
            'has_more' => $hasMore,
        ];
    }
}
