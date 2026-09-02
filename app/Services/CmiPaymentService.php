<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class CmiPaymentService
{
    public function paymentForm(Order $order): array
    {
        $this->ensureConfigured();

        $fields = [
            'clientid' => config('services.cmi.client_id'),
            'amount' => number_format((float) $order->total_price, 2, '.', ''),
            'oid' => $this->orderReference($order),
            'okUrl' => config('services.cmi.ok_url'),
            'failUrl' => config('services.cmi.fail_url'),
            'callbackUrl' => config('services.cmi.callback_url'),
            'TranType' => config('services.cmi.transaction_type'),
            'currency' => config('services.cmi.currency'),
            'rnd' => (string) Str::uuid(),
            'storetype' => config('services.cmi.store_type'),
            'hashAlgorithm' => 'ver3',
            'lang' => config('services.cmi.language'),
            'BillToName' => $order->user?->name ?: 'Client',
            'email' => $order->user?->email,
            'tel' => $order->phone,
        ];

        $fields['hash'] = $this->generateHash($fields);

        return [
            'gateway_url' => config('services.cmi.gateway_url'),
            'method' => 'POST',
            'fields' => $fields,
        ];
    }

    public function configured(): bool
    {
        return (bool) config('services.cmi.client_id') && (bool) config('services.cmi.store_key');
    }

    public function isAccepted(array $payload): bool
    {
        $code = (string) Arr::get($payload, 'ProcReturnCode', Arr::get($payload, 'procReturnCode', ''));
        $response = strtoupper((string) Arr::get($payload, 'Response', Arr::get($payload, 'response', '')));

        return $code === '00' || in_array($response, ['APPROVED', 'ACCEPTED', 'SUCCESS'], true);
    }

    public function orderIdFromPayload(array $payload): ?int
    {
        $oid = (string) Arr::get($payload, 'oid', Arr::get($payload, 'OID', ''));

        if (preg_match('/^ORDER-(\d+)-/', $oid, $matches)) {
            return (int) $matches[1];
        }

        return ctype_digit($oid) ? (int) $oid : null;
    }

    public function verifyCallbackHash(array $payload): bool
    {
        $receivedHash = (string) Arr::get($payload, 'HASH', Arr::get($payload, 'hash', ''));

        if ($receivedHash === '' || ! config('services.cmi.store_key')) {
            return false;
        }

        if (! empty($payload['HASHPARAMS'])) {
            $hashParams = explode(':', trim((string) $payload['HASHPARAMS'], ':'));
            $plain = collect($hashParams)
                ->map(fn ($key) => $this->escapeValue((string) ($payload[$key] ?? '')))
                ->implode('|');
            $expected = base64_encode(pack('H*', hash('sha512', $plain.'|'.$this->escapeValue((string) config('services.cmi.store_key')))));

            return hash_equals($receivedHash, $expected);
        }

        return hash_equals($receivedHash, $this->generateHash($payload));
    }

    public function transactionReference(array $payload): ?string
    {
        return Arr::get($payload, 'TransId')
            ?? Arr::get($payload, 'TransID')
            ?? Arr::get($payload, 'xid')
            ?? Arr::get($payload, 'oid');
    }

    private function generateHash(array $fields): string
    {
        $storeKey = config('services.cmi.store_key');
        $hashFields = collect($fields)
            ->reject(fn ($value, $key) => in_array(strtolower((string) $key), ['hash', 'encoding'], true))
            ->sortKeys(SORT_STRING | SORT_FLAG_CASE)
            ->map(fn ($value) => $this->escapeValue((string) $value))
            ->implode('|');

        $plain = $hashFields.'|'.$this->escapeValue((string) $storeKey);

        return base64_encode(pack('H*', hash('sha512', $plain)));
    }

    private function escapeValue(string $value): string
    {
        return str_replace('|', '\|', str_replace('\\', '\\\\', $value));
    }

    private function orderReference(Order $order): string
    {
        return 'ORDER-'.$order->id.'-'.$order->created_at?->format('YmdHis');
    }

    private function ensureConfigured(): void
    {
        if (! config('services.cmi.client_id') || ! config('services.cmi.store_key')) {
            throw new RuntimeException('CMI payment is not configured. Please set CMI_CLIENT_ID and CMI_STORE_KEY.');
        }
    }
}
