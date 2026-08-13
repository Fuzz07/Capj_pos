<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\NotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $categories = [
            'All',
            'Creamy Milktea',
            'Salty Cheese',
            'Cheesecake',
            'Frappe',
            'Soda Float',
            'Fruit Latte',
            'Fruit Smoothie',
            'Fruitshake',
            'Halo-Halo',
            'Snacks',
            'French Fries',
            'Add-on',
            'Rice Meal',
            'Extra',
            'Hot Coffee',
            'Iced Coffee',
            'Takoyaki',
            'Lemonade',
        ];

        $items = Inventory::active()->orderBy('name')->get();

        $takeoutFeeAmount = Setting::getFloat('takeout_fee_amount', 5);
        $takeoutFeePerItems = max(1, Setting::getInt('takeout_fee_per_items', 2));
        $gcashNumber = Setting::get('gcash_number', config('pos.gcash.number'));
        $gcashName = Setting::get('gcash_name', '');

        return view('pos.index', compact(
            'items', 'categories',
            'takeoutFeeAmount', 'takeoutFeePerItems', 'gcashNumber', 'gcashName'
        ));
    }

    public function processOrder(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'nullable|string|max:150',
            'order_type' => 'required|in:Dine-in,Take-out',
            'payment_method' => 'required|in:cash,gcash',
            'amount_paid' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|exists:inventory,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $subtotal = 0;
            $itemsToProcess = [];

            foreach ($validated['items'] as $cartItem) {
                $inventory = Inventory::findOrFail($cartItem['id']);

                if ($inventory->stock_qty < $cartItem['qty']) {
                    throw new Exception("Insufficient stock for '{$inventory->name}'. Only {$inventory->stock_qty} left.");
                }

                $lineTotal = $inventory->price * $cartItem['qty'];
                $subtotal += $lineTotal;

                $itemsToProcess[] = [
                    'inventory' => $inventory,
                    'qty' => $cartItem['qty'],
                    'unit_price' => $inventory->price,
                    'line_total' => $lineTotal,
                ];
            }

            $totalQty = 0;
            foreach ($itemsToProcess as $item) {
                $totalQty += $item['qty'];
            }

            $takeoutFee = 0.00;
            if ($validated['order_type'] === 'Take-out') {
                $feeAmount = Setting::getFloat('takeout_fee_amount', 5);
                $perItems = max(1, Setting::getInt('takeout_fee_per_items', 2));
                $takeoutFee = (float) (ceil($totalQty / $perItems) * $feeAmount);
            }
            $totalAmount = $subtotal + $takeoutFee;

            $amountPaid = (float)($validated['amount_paid'] ?? 0);
            if ($validated['payment_method'] === 'gcash') {
                $amountPaid = $totalAmount;
            }

            if ($validated['payment_method'] === 'cash' && $amountPaid < $totalAmount) {
                throw new Exception("Paid amount (₱" . number_format($amountPaid, 2) . ") is less than total amount (₱" . number_format($totalAmount, 2) . ").");
            }

            $changeDue = max(0, $amountPaid - $totalAmount);

            $order = Order::create([
                'user_id' => auth()->id(),
                'customer_name' => $validated['customer_name'] ?? 'Walk-in Customer',
                'order_type' => $validated['order_type'],
                'takeout_fee' => $takeoutFee,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'change_due' => $changeDue,
                'status' => 'completed',
                'payment_method' => $validated['payment_method'],
            ]);

            foreach ($itemsToProcess as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'inventory_id' => $item['inventory']->id,
                    'qty' => $item['qty'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);

                // Deduct stock
                $item['inventory']->decrement('stock_qty', $item['qty']);

                // Check low stock & out of stock notifications
                $item['inventory']->refresh();
                NotificationService::checkAndNotifyLowStock($item['inventory']);
            }

            DB::commit();

            if ($validated['payment_method'] === 'gcash') {
                NotificationService::notifyGcashPayment($order);
            }

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'ORDER_CREATED',
                'description' => "Created {$validated['order_type']} order #{$order->id} for {$order->customer_name} totaling ₱" . number_format($totalAmount, 2)
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Order created successfully!',
                    'order_id' => $order->id,
                    'receipt_url' => route('pos.receipt', $order->id),
                ]);
            }

            return redirect()->route('pos.receipt', $order->id)->with('success', 'Order processed successfully!');

        } catch (Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function receipt(Order $order)
    {
        $order->load('user', 'items.inventory');
        return view('pos.receipt', compact('order'));
    }

    public function cancelOrder(Order $order)
    {
        if ($order->status === 'cancelled') {
            return back()->with('error', 'Order is already cancelled.');
        }

        try {
            DB::beginTransaction();

            // Restore inventory stock
            foreach ($order->items as $item) {
                if ($item->inventory) {
                    $item->inventory->increment('stock_qty', $item->qty);
                }
            }

            $order->update(['status' => 'cancelled']);

            DB::commit();

            ActivityLog::create([
                'user_id' => auth()->id(),
                'action' => 'ORDER_CANCELLED',
                'description' => "Cancelled order #{$order->id} and restored stock."
            ]);

            return back()->with('success', "Order #{$order->id} has been cancelled and stock returned.");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', "Failed to cancel order: " . $e->getMessage());
        }
    }
}
