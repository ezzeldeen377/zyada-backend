<?php

namespace App\Services;

use App\Models\PaymentRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TelrPaymentService
{
    private const ENDPOINT = 'https://secure.telr.com/gateway/order.json';

    private const MOBILE_ENDPOINT = 'https://secure.telr.com/api/v1/orders';

    private const WEBHOOK_FIELDS = [
        'tran_store',
        'tran_type',
        'tran_class',
        'tran_test',
        'tran_ref',
        'tran_prevref',
        'tran_firstref',
        'tran_order',
        'tran_currency',
        'tran_amount',
        'tran_cartid',
        'tran_desc',
        'tran_status',
        'tran_authcode',
        'tran_authmessage',
    ];

    public function __construct(private readonly array $config) {}

    public function createPayload(PaymentRequest $payment, array $returnUrls, object|array|null $payer = null): array
    {
        $payer = is_object($payer) ? get_object_vars($payer) : ($payer ?? []);
        $amount = number_format((float) $payment->payment_amount, 2, '.', '');
        $payload = [
            'method' => 'create',
            'store' => (int) $this->required('store_id'),
            'authkey' => $this->required('authkey'),
            'framed' => 0,
            'order' => [
                'cartid' => (string) $payment->id,
                'test' => ($this->config['mode'] ?? 'live') === 'test' ? 1 : 0,
                'amount' => $amount,
                'currency' => strtoupper((string) $payment->currency_code),
                'description' => 'Payment '.$payment->id,
            ],
            'return' => [
                'authorised' => $returnUrls['authorised'],
                'declined' => $returnUrls['declined'],
                'cancelled' => $returnUrls['cancelled'],
            ],
        ];

        $panels = trim((string) ($this->config['panels'] ?? ''));
        if ($panels !== '') {
            $payload['panels'] = $panels;
        }

        $email = $payer['email'] ?? null;
        if ($email) {
            $payload['customer'] = ['email' => $email];
            if (! empty($payer['name'])) {
                $name = preg_split('/\s+/', trim((string) $payer['name']), 2);
                $payload['customer']['name'] = [
                    'forenames' => $name[0],
                    'surname' => $name[1] ?? '',
                ];
            }
            if (! empty($payer['phone'])) {
                $payload['customer']['address']['mobile'] = $payer['phone'];
            }
        }

        return $payload;
    }

    public function checkPayload(string $reference): array
    {
        return [
            'method' => 'check',
            'store' => (int) $this->required('store_id'),
            'authkey' => $this->required('authkey'),
            'order' => ['ref' => $reference],
        ];
    }

    public function create(PaymentRequest $payment, array $returnUrls, object|array|null $payer = null): array
    {
        return $this->post($this->createPayload($payment, $returnUrls, $payer));
    }

    public function check(string $reference): array
    {
        return $this->post($this->checkPayload($reference));
    }

    /**
     * Payload for the Telr Mobile SDK createOrder API (POST /api/v1/orders),
     * whose response carries the _links.auth / _links.self URLs the native SDK needs.
     */
    public function createMobilePayload(PaymentRequest $payment, array $customer, ?string $webhookUrl = null): array
    {
        $payload = [
            'transactionType' => 'SALE',
            'cartId' => (string) $payment->id,
            'amount' => [
                'value' => round((float) $payment->payment_amount, 2),
                'currency' => strtoupper((string) $payment->currency_code),
            ],
            'description' => substr('Order payment '.$payment->id, 0, 63),
            'test' => ($this->config['mode'] ?? 'live') === 'test',
            'customer' => $customer,
        ];

        if ($webhookUrl !== null && $webhookUrl !== '') {
            $payload['webhooks'] = [['url' => $webhookUrl]];
        }

        return $payload;
    }

    public function createMobileOrder(PaymentRequest $payment, array $customer, ?string $webhookUrl = null): array
    {
        /** @var Response $response */
        $response = Http::withBasicAuth($this->required('store_id'), $this->mobileApiKey())
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post(self::MOBILE_ENDPOINT, $this->createMobilePayload($payment, $customer, $webhookUrl));

        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('Telr mobile order API request failed.');
        }

        return $response->json();
    }

    public static function extractMobileLinks(array $response): ?array
    {
        $tokenUrl = data_get($response, '_links.auth.href');
        $orderUrl = data_get($response, '_links.self.href');

        if (! is_string($tokenUrl) || $tokenUrl === '' || ! is_string($orderUrl) || $orderUrl === '') {
            return null;
        }

        $links = ['token_url' => $tokenUrl, 'order_url' => $orderUrl];

        $ref = data_get($response, 'ref');
        if (is_string($ref) && $ref !== '') {
            $links['order_ref'] = $ref;
        }

        return $links;
    }

    public static function fromAddonSettings(): ?self
    {
        try {
            $config = DB::table('addon_settings')
                ->where('key_name', 'telr')
                ->where('settings_type', 'payment_config')
                ->first();
        } catch (\Throwable) {
            return null;
        }

        if (! $config) {
            return null;
        }

        $mode = $config->mode ?: 'test';
        $values = json_decode($config->{$mode.'_values'} ?? '{}', true);
        if (! is_array($values) || (string) ($values['status'] ?? '0') !== '1') {
            return null;
        }

        return new self(array_merge($values, ['mode' => $mode]));
    }

    private function mobileApiKey(): string
    {
        $key = trim((string) ($this->config['mobile_api_key'] ?? ''));

        return $key !== '' ? $key : $this->required('authkey');
    }

    public function isPaid(array $response): bool
    {
        return (int) data_get($response, 'order.status.code') === 3;
    }

    public function isAuthorisedTransaction(array $webhook): bool
    {
        return in_array(strtoupper((string) ($webhook['tran_status'] ?? '')), ['A', 'H'], true);
    }

    public function verifyWebhook(array $webhook): bool
    {
        $provided = (string) ($webhook['tran_check'] ?? '');
        if ($provided === '') {
            return false;
        }

        $secret = trim((string) ($this->config['webhook_secret'] ?? ''));
        if ($secret === '') {
            $secret = $this->required('authkey');
        }

        return hash_equals(self::signWebhook($webhook, $secret), $provided);
    }

    public static function signWebhook(array $webhook, string $secret): string
    {
        $values = array_map(
            static fn (string $field): string => rawurldecode(trim((string) ($webhook[$field] ?? ''))),
            self::WEBHOOK_FIELDS
        );

        return sha1($secret.':'.implode(':', $values));
    }

    private function post(array $payload): array
    {
        /** @var Response $response */
        $response = Http::acceptJson()->asJson()->post(self::ENDPOINT, $payload);
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('Telr API request failed.');
        }

        return $response->json();
    }

    private function required(string $key): string
    {
        $value = trim((string) ($this->config[$key] ?? ''));
        if ($value === '') {
            throw new RuntimeException('Telr '.$key.' is not configured.');
        }

        return $value;
    }
}
