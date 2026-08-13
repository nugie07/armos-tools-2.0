<?php

namespace App\Services\ConvertSend;

use App\Services\Tms\TmsDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ConvertSendService
{
    public function __construct(
        private ExcelToJsonConverter $converter,
        private OrderFeedClient $feedClient,
    ) {}

    /**
     * Upload Excel → convert (order_data/order_detail) → auth → feed order.
     */
    public function handle(UploadedFile $file): array
    {
        $steps = [];
        $stored = $file->storeAs('convert_send', now()->format('Ymd_His').'_'.$file->getClientOriginalName());
        $steps[] = ['status' => 'OK', 'message' => 'File tersimpan: '.$stored];

        $fullPath = Storage::path($stored);
        try {
            $orders = $this->converter->convert($fullPath);
            $steps[] = ['status' => 'OK', 'message' => 'Konversi Excel → JSON: '.count($orders).' order'];
        } catch (\Throwable $e) {
            $steps[] = ['status' => 'ERROR', 'message' => 'Gagal konversi: '.$e->getMessage()];
            throw $e;
        }

        $converted = [
            'source' => $file->getClientOriginalName(),
            'stored_path' => $stored,
            'orders' => $orders,
        ];

        if ($orders === []) {
            $steps[] = ['status' => 'ERROR', 'message' => 'Tidak ada order hasil konversi. Feed dilewati.'];

            return [
                'steps' => $steps,
                'converted_json' => $converted,
            ];
        }

        try {
            $cfg = $this->resolveAuthConfig();
        } catch (RuntimeException $e) {
            $steps[] = ['status' => 'ERROR', 'message' => $e->getMessage()];

            return [
                'steps' => $steps,
                'converted_json' => $converted,
            ];
        }

        try {
            $token = $this->feedClient->loginGetToken($cfg['auth_url'], $cfg['username'], $cfg['password']);
            $steps[] = ['status' => 'OK', 'message' => 'Auth token berhasil ('.$cfg['auth_url'].')'];
        } catch (\Throwable $e) {
            $steps[] = ['status' => 'ERROR', 'message' => 'Auth gagal: '.$e->getMessage()];

            return [
                'steps' => $steps,
                'converted_json' => $converted,
            ];
        }

        $ok = 0;
        $fail = 0;
        foreach ($orders as $order) {
            $payload = $this->feedClient->buildPayload($order);
            $ref = $payload['outbound_reference'] ?? '(tanpa ref)';
            try {
                $resp = $this->feedClient->sendOrder($cfg['feed_url'], $token, $payload);
                $content = $resp->body();
                if (str_starts_with((string) $resp->header('Content-Type'), 'application/json')) {
                    $json = $resp->json();
                    if ($json !== null) {
                        $content = json_encode($json, JSON_UNESCAPED_UNICODE) ?: $content;
                    }
                }
                $snippet = mb_substr((string) $content, 0, 500);
                if ($resp->successful()) {
                    $steps[] = ['status' => 'OK', 'message' => "Kirim {$ref}: {$resp->status()} -> {$snippet}"];
                    $ok++;
                } else {
                    $steps[] = ['status' => 'ERROR', 'message' => "Kirim {$ref}: {$resp->status()} -> {$snippet}"];
                    $fail++;
                }
            } catch (\Throwable $e) {
                $steps[] = ['status' => 'ERROR', 'message' => "Kirim {$ref}: ".$e->getMessage()];
                $fail++;
            }
        }

        $steps[] = ['status' => $fail === 0 ? 'OK' : 'ERROR', 'message' => "Selesai kirim. Berhasil: {$ok}, Gagal: {$fail}. Target: {$cfg['feed_url']}"];

        return [
            'steps' => $steps,
            'converted_json' => $converted,
            'token_acquired' => true,
            'sent_ok' => $ok,
            'sent_fail' => $fail,
        ];
    }

    /**
     * @return array{auth_url:string,feed_url:string,username:string,password:string}
     */
    protected function resolveAuthConfig(): array
    {
        return [
            'auth_url' => $this->requiredSetting('AUTH_URL'),
            'feed_url' => $this->requiredSetting('FEED_ORDER_URL'),
            'username' => $this->requiredSetting('SEND_ORDER_USERNAME'),
            'password' => $this->requiredSetting('SEND_ORDER_PASSWORD'),
        ];
    }

    protected function requiredSetting(string $key): string
    {
        $value = TmsDatabase::setting($key);
        if ($value === null || $value === '') {
            throw new RuntimeException(
                "{$key} belum diisi di Env Configuration (tab General Config)."
            );
        }

        return $value;
    }
}
