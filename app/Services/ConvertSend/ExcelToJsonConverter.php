<?php

namespace App\Services\ConvertSend;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class ExcelToJsonConverter
{
    /** @var list<string> */
    private const ITEM_IDENTIFIER_COLS = [
        'line_id',
        'product_id',
        'product_description',
        'group_id',
        'group_description',
        'product_type',
        'qty',
        'uom',
        'pack_id',
        'product_net_price',
    ];

    /**
     * Port of Flask konversi.py: sheet order_data + order_detail → list of orders with items.
     *
     * @return list<array<string, mixed>>
     */
    public function convert(string $excelPath): array
    {
        if (! is_file($excelPath)) {
            throw new RuntimeException("File Excel tidak ditemukan: {$excelPath}");
        }

        $spreadsheet = IOFactory::load($excelPath);

        try {
            $orderRows = $this->sheetToAssoc($this->requireSheet($spreadsheet, 'order_data'));
            $detailRows = $this->sheetToAssoc($this->requireSheet($spreadsheet, 'order_detail'));
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        if ($orderRows === []) {
            throw new RuntimeException("Sheet 'order_data' di dalam file Excel kosong.");
        }
        if (! array_key_exists('id', $orderRows[0])) {
            throw new RuntimeException("Kolom 'id' pada sheet order_data tidak ditemukan.");
        }
        if ($detailRows !== [] && ! array_key_exists('order_data_id', $detailRows[0])) {
            throw new RuntimeException("Kolom 'order_data_id' pada sheet order_detail tidak ditemukan.");
        }

        $detailsByOrder = [];
        foreach ($detailRows as $detail) {
            $key = $this->normalizeRelKey($detail['order_data_id'] ?? null);
            if ($key === '') {
                continue;
            }
            $detailsByOrder[$key][] = $detail;
        }

        $orders = [];
        foreach ($orderRows as $orderRow) {
            $header = $orderRow;
            $orderKey = $this->normalizeRelKey($orderRow['id'] ?? null);
            $header['items'] = $this->buildItems($detailsByOrder[$orderKey] ?? []);
            $orders[] = $header;
        }

        return $orders;
    }

    private function requireSheet(Spreadsheet $spreadsheet, string $name): Worksheet
    {
        $sheet = $spreadsheet->getSheetByName($name);
        if ($sheet === null) {
            throw new RuntimeException("Sheet '{$name}' tidak ditemukan. Pastikan file punya sheet order_data dan order_detail.");
        }

        return $sheet;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sheetToAssoc(Worksheet $sheet): array
    {
        $rows = $sheet->toArray(null, true, false, false);
        if ($rows === []) {
            return [];
        }

        $headerRow = array_shift($rows);
        $headers = [];
        foreach ($headerRow as $i => $name) {
            $headers[$i] = strtolower(trim((string) $name));
        }

        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row) || $this->rowIsEmpty($row)) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $i => $col) {
                if ($col === '') {
                    continue;
                }
                $assoc[$col] = $this->normalizeCell($col, $row[$i] ?? null);
            }
            $out[] = $assoc;
        }

        return $out;
    }

    /**
     * @param list<mixed> $row
     */
    private function rowIsEmpty(array $row): bool
    {
        foreach ($row as $v) {
            if ($v !== null && $v !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeCell(string $column, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }
        if (in_array($column, ['product_id', 'pack_id', 'uom', 'conversion_uom'], true)) {
            return $this->normalizeTextId($column, $value);
        }
        if (is_string($value)) {
            $trim = trim($value);
            if ($trim === '' || strtolower($trim) === 'nan') {
                return null;
            }

            return $trim;
        }

        return $value;
    }

    private function normalizeTextId(string $column, mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        if ($s === '' || strtolower($s) === 'nan') {
            return null;
        }
        if (str_ends_with($s, '.0') && ctype_digit(substr($s, 0, -2))) {
            $s = substr($s, 0, -2);
        }
        if ($column === 'pack_id' && ctype_digit($s) && strlen($s) < 4) {
            return str_pad($s, 4, '0', STR_PAD_LEFT);
        }

        return $s;
    }

    private function normalizeRelKey(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_numeric($value)) {
            return (string) (int) $value;
        }

        return trim((string) $value);
    }

    /**
     * @param list<array<string, mixed>> $orderDetails
     * @return list<array<string, mixed>>
     */
    private function buildItems(array $orderDetails): array
    {
        if ($orderDetails === []) {
            return [];
        }

        $groups = [];
        foreach ($orderDetails as $row) {
            $keyParts = [];
            foreach (self::ITEM_IDENTIFIER_COLS as $col) {
                $keyParts[] = json_encode($row[$col] ?? null);
            }
            $key = implode('|', $keyParts);
            if (! isset($groups[$key])) {
                $item = [];
                foreach (self::ITEM_IDENTIFIER_COLS as $col) {
                    $item[$col] = $row[$col] ?? null;
                }
                if ($item['product_id'] !== null) {
                    $item['product_id'] = (string) $item['product_id'];
                }
                if ($item['uom'] !== null) {
                    $item['uom'] = (string) $item['uom'];
                }
                $item['conversion'] = [];
                $groups[$key] = $item;
            }
            $convUom = $row['conversion_uom'] ?? null;
            $groups[$key]['conversion'][] = [
                'uom' => $convUom !== null ? (string) $convUom : null,
                'numerator' => (int) ($row['conversion_numerator'] ?? 0),
                'denominator' => (int) ($row['conversion_denominator'] ?? 1),
            ];
        }

        return array_values($groups);
    }
}
