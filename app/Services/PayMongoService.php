<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayMongoService
{
    protected function getSecretKey(): string
    {
        $useTest = config('pos.paymongo.use_test', true);
        return $useTest 
            ? config('pos.paymongo.secret_key_test') 
            : config('pos.paymongo.secret_key_live');
    }

    public function call(string $method, string $path, array $data = [])
    {
        $url = 'https://api.paymongo.com/v1' . $path;
        $secretKey = $this->getSecretKey();

        $request = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($secretKey . ':'),
        ])->timeout(30);

        $response = match (strtoupper($method)) {
            'POST' => $request->post($url, $data),
            'GET' => $request->get($url, $data),
            default => throw new Exception("Unsupported HTTP method: $method"),
        };

        if ($response->failed()) {
            $json = $response->json();
            $msg = $json['errors'][0]['detail'] ?? ($json['error']['message'] ?? $response->body());
            Log::error('PayMongo API Error', ['status' => $response->status(), 'response' => $json]);
            throw new Exception("PayMongo API Error ({$response->status()}): $msg");
        }

        return $response->json();
    }

    public function createGcashSource(int $amountCentavos, string $description, string $returnUrl): array
    {
        return $this->call('POST', '/sources', [
            'data' => [
                'attributes' => [
                    'type' => 'gcash',
                    'amount' => $amountCentavos,
                    'currency' => 'PHP',
                    'redirect' => [
                        'success' => $returnUrl,
                        'failed' => $returnUrl,
                    ],
                ],
            ],
        ]);
    }

    public function getSource(string $id): array
    {
        return $this->call('GET', "/sources/{$id}");
    }

    public function getPaymentIntent(string $id): array
    {
        return $this->call('GET', "/payment_intents/{$id}");
    }
}
