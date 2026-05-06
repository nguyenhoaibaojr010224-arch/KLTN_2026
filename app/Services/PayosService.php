<?php

namespace App\Services;

use App\Models\HoaDon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayosService
{
    public function isConfigured(): bool
    {
        return filled($this->clientId())
            && filled($this->apiKey())
            && filled($this->checksumKey());
    }

    public function createPaymentLink(HoaDon $hoaDon, array $items = []): array
    {
        $amount = (int) round((float) $hoaDon->tien_thanh_toan);
        $orderCode = (int) $hoaDon->id_hoa_don;
        $description = Str::limit('PHARMAGO ' . $hoaDon->ma_hoa_don, 25, '');

        return $this->createPaymentLinkFromData($orderCode, $amount, $description, $items);
    }

    public function createPaymentLinkFromData(int $orderCode, int $amount, string $description, array $items = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Chưa cấu hình PAYOS_CLIENT_ID, PAYOS_API_KEY hoặc PAYOS_CHECKSUM_KEY.');
        }

        $returnUrl = (string) config('services.payos.return_url');
        $cancelUrl = (string) config('services.payos.cancel_url');
        $description = Str::limit($description, 25, '');

        $signaturePayload = [
            'amount' => $amount,
            'cancelUrl' => $cancelUrl,
            'description' => $description,
            'orderCode' => $orderCode,
            'returnUrl' => $returnUrl,
        ];

        $payload = [
            ...$signaturePayload,
            'items' => array_values(array_map(fn (array $item): array => [
                'name' => Str::limit((string) ($item['name'] ?? 'San pham'), 100, ''),
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'price' => max(0, (int) round((float) ($item['price'] ?? 0))),
            ], $items)),
            'signature' => $this->signature($signaturePayload),
        ];

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->withHeaders([
                    'x-client-id' => $this->clientId(),
                    'x-api-key' => $this->apiKey(),
                ])
                ->post($this->baseUrl() . '/v2/payment-requests', $payload)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = $exception->response?->json('desc')
                ?: $exception->response?->json('message')
                ?: 'Không tạo được link thanh toán PayOS.';

            throw new RuntimeException($message, previous: $exception);
        }

        if (($response['code'] ?? null) !== '00' || ! is_array($response['data'] ?? null)) {
            throw new RuntimeException((string) ($response['desc'] ?? 'PayOS từ chối tạo link thanh toán.'));
        }

        return [
            'order_code' => $orderCode,
            'payment_link_id' => (string) ($response['data']['paymentLinkId'] ?? ''),
            'checkout_url' => (string) ($response['data']['checkoutUrl'] ?? ''),
            'qr_code' => (string) ($response['data']['qrCode'] ?? ''),
            'status' => (string) ($response['data']['status'] ?? 'PENDING'),
            'raw' => $response,
        ];
    }

    public function verifyWebhook(array $payload): bool
    {
        $data = $payload['data'] ?? null;
        $signature = (string) ($payload['signature'] ?? '');

        if (! is_array($data) || $signature === '' || ! filled($this->checksumKey())) {
            return false;
        }

        return hash_equals($this->signature($data), $signature);
    }

    public function signature(array $data): string
    {
        ksort($data);

        $raw = collect($data)
            ->map(fn ($value, string $key): string => $key . '=' . $this->stringifySignatureValue($value))
            ->implode('&');

        return hash_hmac('sha256', $raw, $this->checksumKey());
    }

    private function stringifySignatureValue(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return (string) $value;
    }

    private function clientId(): string
    {
        return (string) config('services.payos.client_id');
    }

    private function apiKey(): string
    {
        return (string) config('services.payos.api_key');
    }

    private function checksumKey(): string
    {
        return (string) config('services.payos.checksum_key');
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.payos.base_url', 'https://api-merchant.payos.vn'), '/');
    }
}
