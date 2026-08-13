<?php

namespace Tests\Unit;

use App\Services\ConvertSend\ExcelToJsonConverter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PHPUnit\Framework\TestCase;

class ExcelToJsonConverterTest extends TestCase
{
    public function test_converts_order_data_and_detail_sheets(): void
    {
        $path = sys_get_temp_dir().'/convert_send_test_'.uniqid().'.xlsx';

        $spreadsheet = new Spreadsheet;
        $order = $spreadsheet->getActiveSheet();
        $order->setTitle('order_data');
        $order->fromArray([
            ['id', 'outbound_reference', 'warehouse_id', 'client_id', 'divisi', 'faktur_date'],
            [1, 'ORDER-PADAMU-001', 'KJR01', 'BBM', 'PADAMU', 46244],
        ], null, 'A1');

        $detail = $spreadsheet->createSheet();
        $detail->setTitle('order_detail');
        $detail->fromArray([
            ['order_data_id', 'line_id', 'product_id', 'product_description', 'group_id', 'group_description', 'product_type', 'qty', 'uom', 'pack_id', 'product_net_price', 'conversion_uom', 'conversion_numerator', 'conversion_denominator'],
            [1, 1, '48364701080044', 'BPS BRAVAS OR VIOLET 144X65ML', '08', 'BODY TREATMENT', 48, 40, 'PCS', '0101', 40000, 'PCS', 1, 1],
        ], null, 'A1');

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        try {
            $orders = (new ExcelToJsonConverter)->convert($path);
            $this->assertCount(1, $orders);
            $this->assertSame('ORDER-PADAMU-001', $orders[0]['outbound_reference']);
            $this->assertCount(1, $orders[0]['items']);
            $this->assertSame('48364701080044', $orders[0]['items'][0]['product_id']);
            $this->assertSame('0101', $orders[0]['items'][0]['pack_id']);
            $this->assertSame('PCS', $orders[0]['items'][0]['conversion'][0]['uom']);
        } finally {
            @unlink($path);
        }
    }

    public function test_missing_sheet_throws(): void
    {
        $path = sys_get_temp_dir().'/convert_send_bad_'.uniqid().'.xlsx';
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->setTitle('Sheet1');
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Sheet 'order_data' tidak ditemukan");

        try {
            (new ExcelToJsonConverter)->convert($path);
        } finally {
            @unlink($path);
        }
    }
}
