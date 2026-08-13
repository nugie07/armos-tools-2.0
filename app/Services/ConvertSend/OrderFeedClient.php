<?php

namespace App\Services\ConvertSend;

use DateTimeImmutable;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OrderFeedClient
{
    /**
     * Port of Flask send_orders.build_payload().
     *
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public function buildPayload(array $order): array
    {
        $payload = [
            'warehouse_id' => $order['warehouse_id'] ?? null,
            'client_id' => $order['client_id'] ?? null,
            'outbound_reference' => $order['outbound_reference'] ?? null,
            'divisi' => $order['divisi'] ?? null,
            'faktur_date' => $this->excelSerialToIso($order['faktur_date'] ?? null),
            'request_delivery_date' => $this->excelSerialToIso($order['request_delivery_date'] ?? null),
            'origin_name' => $order['origin_name'] ?? null,
            'origin_address_1' => $order['origin_address_1'] ?? '',
            'origin_address_2' => $order['origin_address_2'] ?? '',
            'origin_city' => $order['origin_city'] ?? '',
            'origin_phone' => $order['origin_phone'] ?? '',
            'origin_email' => $order['origin_email'] ?? '',
            'destination_id' => $order['destination_id'] ?? null,
            'destination_name' => $order['destination_name'] ?? null,
            'destination_address_1' => $order['destination_address_1'] ?? '',
            'destination_address_2' => $order['destination_address_2'] ?? '',
            'destination_city' => $order['destination_city'] ?? '',
            'destination_zip_code' => $order['destination_zip_code'] ?? '',
            'destination_phone' => $order['destination_phone'] ?? '',
            'destination_email' => $order['destination_email'] ?? '',
            'order_type' => $order['order_type'] ?? null,
            'items' => [],
        ];

        foreach ($order['items'] ?? [] as $item) {
            $productType = $item['product_type'] ?? null;
            $itemPayload = [
                'warehouse_id' => $order['warehouse_id'] ?? null,
                'line_id' => isset($item['line_id']) && $item['line_id'] !== null ? (string) $item['line_id'] : '',
                'product_id' => isset($item['product_id']) && $item['product_id'] !== null ? (string) $item['product_id'] : '',
                'product_description' => $item['product_description'] ?? '',
                'group_id' => isset($item['group_id']) && $item['group_id'] !== null ? (string) $item['group_id'] : '',
                'group_description' => $item['group_description'] ?? '',
                'product_type' => $productType !== null ? str_pad((string) $productType, 3, '0', STR_PAD_LEFT) : '',
                'qty' => $item['qty'] ?? 0,
                'uom' => $item['uom'] ?? '',
                'pack_id' => isset($item['pack_id']) && $item['pack_id'] !== null ? (string) $item['pack_id'] : '',
                'product_net_price' => $item['product_net_price'] ?? 0,
                'conversion' => [],
                'image_url' => [''],
            ];

            foreach ($item['conversion'] ?? [] as $conv) {
                $itemPayload['conversion'][] = [
                    'uom' => $conv['uom'] ?? '',
                    'numerator' => (int) ($conv['numerator'] ?? 0),
                    'denominator' => (int) ($conv['denominator'] ?? 1),
                ];
            }

            $payload['items'][] = $itemPayload;
        }

        return $payload;
    }

    public function loginGetToken(string $authUrl, string $username, string $password): string
    {
        $response = Http::timeout(30)->post($authUrl, [
            'username' => $username,
            'password' => $password,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('HTTP '.$response->status().' dari AUTH_URL');
        }

        $json = $response->json();
        if (is_array($json) && array_key_exists('success', $json) && ! $json['success']) {
            throw new RuntimeException('Login gagal: '.json_encode($json));
        }

        $token = $json['token'] ?? $json['access_token'] ?? $json['data']['token'] ?? null;
        if (! $token) {
            throw new RuntimeException('Token tidak ditemukan di response AUTH_URL');
        }

        return (string) $token;
    }

    public function sendOrder(string $feedUrl, string $token, array $payload): Response
    {
        return Http::timeout(60)
            ->withToken($token)
            ->acceptJson()
            ->asJson()
            ->post($feedUrl, $payload);
    }

    public function excelSerialToIso(mixed $dateValue): ?string
    {
        if ($dateValue === null || $dateValue === '') {
            return null;
        }
        if ($dateValue instanceof \DateTimeInterface) {
            return $dateValue->format('Y-m-d');
        }
        if (is_string($dateValue)) {
            $s = trim($dateValue);
            if ($s === '') {
                return null;
            }
            try {
                return (new DateTimeImmutable($s))->format('Y-m-d');
            } catch (\Exception) {
                foreach (['d/m/Y', 'm/d/Y', 'Y/m/d', 'd-m-Y', 'm-d-Y'] as $fmt) {
                    $dt = DateTimeImmutable::createFromFormat($fmt, $s);
                    if ($dt instanceof DateTimeImmutable) {
                        return $dt->format('Y-m-d');
                    }
                }

                return $s;
            }
        }

        try {
            $serial = (int) $dateValue;
            $base = new DateTimeImmutable('1899-12-30');

            return $base->modify("+{$serial} days")->format('Y-m-d');
        } catch (\Exception) {
            return null;
        }
    }
}
