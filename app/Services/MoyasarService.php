<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MoyasarService
{
    private string $apiKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('moyasar.api_key');
        $this->baseUrl = config('moyasar.base_url');
    }

    public function createPayment(int $amountInHalalas, string $currency, string $description, string $callbackUrl, array $source): array
    {
        $response = Http::withBasicAuth($this->apiKey, '')
            ->post("{$this->baseUrl}/payments", [
                'amount' => $amountInHalalas,
                'currency' => $currency,
                'description' => $description,
                'callback_url' => $callbackUrl,
                'source' => $source,
            ]);

        return $response->json();
    }

    public function fetchPayment(string $paymentId): array
    {
        $response = Http::withBasicAuth($this->apiKey, '')
            ->get("{$this->baseUrl}/payments/{$paymentId}");

        return $response->json();
    }

    public function refundPayment(string $paymentId, ?int $amount = null): array
    {
        $data = [];
        if ($amount) {
            $data['amount'] = $amount;
        }

        $response = Http::withBasicAuth($this->apiKey, '')
            ->post("{$this->baseUrl}/payments/{$paymentId}/refund", $data);

        return $response->json();
    }

    public function getPublishableKey(): string
    {
        return config('moyasar.publishable_key');
    }

    public function getCallbackUrl(): string
    {
        return config('moyasar.callback_url');
    }
}
