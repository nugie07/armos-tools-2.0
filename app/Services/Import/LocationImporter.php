<?php

namespace App\Services\Import;

use App\Services\Tms\TmsDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class LocationImporter
{
    public function import(UploadedFile $file, string $env): array
    {
        $conn = TmsDatabase::byEnv($env);
        $messages = [];
        $createdBy = Auth::user()->nama ?? 'armos-tools';

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        if (count($rows) < 2) {
            return ['messages' => ['File kosong / tidak ada data'], 'log_data_filename' => null];
        }

        $header = array_shift($rows);
        $map = [];
        foreach ($header as $col => $name) {
            $map[strtolower(trim((string) $name))] = $col;
        }

        $insertedParent = 0;
        $insertedChild = 0;
        $skipped = 0;

        foreach ($rows as $i => $row) {
            $code = trim((string) ($row[$map['code'] ?? 'A'] ?? ''));
            $name = trim((string) ($row[$map['name'] ?? 'B'] ?? ''));
            if ($code === '') {
                continue;
            }

            $existsChild = (int) (TmsDatabase::select($conn, 'SELECT COUNT(*) AS c FROM mst_location_child WHERE code = ?', [$code])[0]['c'] ?? 0);
            if ($existsChild > 0) {
                $skipped++;
                $messages[] = "Skip child existing: {$code}";
                continue;
            }

            $existsParent = (int) (TmsDatabase::select($conn, 'SELECT COUNT(*) AS c FROM mst_location_parent WHERE code = ?', [$code])[0]['c'] ?? 0);
            if ($existsParent === 0) {
                TmsDatabase::affectingStatement(
                    $conn,
                    'INSERT INTO mst_location_parent (code, name, created_by, created_date) VALUES (?, ?, ?, CURRENT_TIMESTAMP)',
                    [$code, $name !== '' ? $name : $code, $createdBy]
                );
                $insertedParent++;
            }

            $parentId = TmsDatabase::select($conn, 'SELECT mst_location_parent_id FROM mst_location_parent WHERE code = ? LIMIT 1', [$code])[0]['mst_location_parent_id'] ?? null;
            if (! $parentId) {
                $messages[] = "Gagal resolve parent untuk {$code}";
                continue;
            }

            // Insert child minimal (kolom wajib); kolom lain null jika tidak ada di Excel
            try {
                TmsDatabase::affectingStatement($conn, <<<'SQL'
INSERT INTO mst_location_child (
  code, name, created_by, created_date, mst_location_parent_id
) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?)
SQL, [$code, $name !== '' ? $name : $code, $createdBy, $parentId]);
                $insertedChild++;
            } catch (\Throwable $e) {
                $messages[] = "Gagal insert child {$code}: ".$e->getMessage();
            }
        }

        $messages[] = "Parent inserted: {$insertedParent}";
        $messages[] = "Child inserted: {$insertedChild}";
        $messages[] = "Skipped: {$skipped}";

        $logName = 'import_lokasi_log_'.now()->format('Ymd_His').'.txt';
        Storage::disk('local')->makeDirectory('import_lokasi');
        Storage::disk('local')->put('import_lokasi/'.$logName, implode("\n", $messages));

        return [
            'messages' => $messages,
            'log_data_filename' => $logName,
        ];
    }

    public function pathForLog(string $filename): string
    {
        $path = Storage::disk('local')->path('import_lokasi/'.$filename);
        if (! is_file($path)) {
            throw new \RuntimeException('Log file tidak ditemukan');
        }

        return $path;
    }
}
