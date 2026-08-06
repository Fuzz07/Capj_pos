<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/paymongo.php';

$piId = $_GET['pi_id'] ?? '';
if (!$piId) {
    echo json_encode(['status' => 'error', 'message' => 'Missing payment intent ID']);
    exit;
}

try {
    $pi = paymongoGetPaymentIntent($piId);
    $status = $pi['data']['attributes']['status'] ?? 'unknown';
    echo json_encode(['status' => $status]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
