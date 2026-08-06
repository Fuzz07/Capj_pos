<?php

function getPayMongoSecretKey() {
    return PAYMONGO_USE_TEST ? PAYMONGO_SECRET_KEY_TEST : PAYMONGO_SECRET_KEY_LIVE;
}

function paymongoCall($method, $path, $data = null) {
    $url = 'https://api.paymongo.com/v1' . $path;
    $secretKey = getPayMongoSecretKey();

    $payload = $data ? json_encode($data) : '';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($secretKey . ':')
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("PayMongo cURL error: $error");
    }

    $result = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("PayMongo JSON decode error: " . json_last_error_msg());
    }

    if ($httpCode >= 400) {
        $msg = $result['errors'][0]['detail'] ?? ($result['error']['message'] ?? 'Unknown error');
        $code = $result['errors'][0]['code'] ?? '';
        throw new Exception("PayMongo API error ($httpCode) [$code] on $path: $msg");
    }

    return $result;
}

function paymongoCreateGcashSource($amountCentavos, $description, $returnUrl) {
    return paymongoCall('POST', '/sources', [
        'data' => [
            'attributes' => [
                'type' => 'gcash',
                'amount' => $amountCentavos,
                'currency' => 'PHP',
                'redirect' => [
                    'success' => $returnUrl,
                    'failed' => $returnUrl
                ]
            ]
        ]
    ]);
}

function paymongoGetSource($id) {
    return paymongoCall('GET', "/sources/$id");
}

function paymongoGetPaymentIntent($id) {
    return paymongoCall('GET', "/payment_intents/$id");
}
