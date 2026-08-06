<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PayMongoService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PayMongoService $payMongoService;

    public function __construct(PayMongoService $payMongoService)
    {
        $this->payMongoService = $payMongoService;
    }

    public function gcashCheckout(Order $order)
    {
        $gcashNumber = config('pos.gcash.number', '09536774000');
        $qrImage = config('pos.gcash.qr_image', 'images/gcash-qr.jpg');
        return view('payments.gcash', compact('order', 'gcashNumber', 'qrImage'));
    }

    public function paymongoCheckout(Order $order)
    {
        try {
            $amountCentavos = (int) round($order->total_amount * 100);
            $returnUrl = route('payments.status', $order->id);

            $result = $this->payMongoService->createGcashSource(
                $amountCentavos,
                "Order #{$order->id} - Captain J POS",
                $returnUrl
            );

            $checkoutUrl = $result['data']['attributes']['redirect']['checkout_url'] ?? null;
            $sourceId = $result['data']['id'] ?? null;

            if ($sourceId) {
                $order->update(['payment_reference' => $sourceId]);
            }

            if ($checkoutUrl) {
                return redirect()->away($checkoutUrl);
            }

            return back()->with('error', 'Unable to initiate PayMongo checkout.');

        } catch (Exception $e) {
            return back()->with('error', 'PayMongo Error: ' . $e->getMessage());
        }
    }

    public function checkStatus(Order $order)
    {
        return response()->json([
            'order_id' => $order->id,
            'status' => $order->status,
            'payment_reference' => $order->payment_reference,
        ]);
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('PayMongo Webhook Received', $payload);

        $type = $payload['data']['attributes']['type'] ?? null;
        $eventData = $payload['data']['attributes']['data'] ?? null;

        if ($type === 'source.chargeable' && $eventData) {
            $sourceId = $eventData['id'] ?? null;

            if ($sourceId) {
                $order = Order::where('payment_reference', $sourceId)->first();
                if ($order && $order->status !== 'completed') {
                    $order->update([
                        'status' => 'completed',
                        'amount_paid' => $order->total_amount,
                    ]);
                    Log::info("Order #{$order->id} marked completed via webhook");
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }
}
