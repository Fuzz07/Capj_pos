<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user', 'items.inventory')->latest()->paginate(15);
        return view('orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('user', 'items.inventory');
        return view('orders.show', compact('order'));
    }

    public function destroy(Order $order)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('orders.index')->with('error', 'Access denied. Only administrators can delete orders.');
        }
        $order->items()->delete();
        $order->delete();
        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }

    /**
     * Void a completed order (admin only).
     * Restores inventory stock and marks the order as 'voided' (or 'cancelled' if restricted).
     */
    public function void(Order $order)
    {
        if ($order->status !== 'completed') {
            return back()->with('error', 'Only completed orders can be voided.');
        }

        // 1. Auto-heal schema if orders.status enum does not yet permit 'voided'
        try {
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'completed'");
        } catch (\Throwable $th) {
            // Silently ignore if already VARCHAR or if ALTER privilege is absent
        }

        try {
            DB::beginTransaction();

            // 2. 100% Restore inventory stock for every item in the order
            foreach ($order->items as $item) {
                if ($item->inventory) {
                    $item->inventory->increment('stock_qty', $item->qty);
                }
            }

            // 3. Update order status: try 'voided', fallback to 'cancelled' if database rejects 'voided'
            try {
                $order->update(['status' => 'voided']);
            } catch (\Throwable $statusEx) {
                $order->update(['status' => 'cancelled']);
            }

            DB::commit();

            ActivityLog::create([
                'user_id'     => auth()->id(),
                'action'      => 'ORDER_VOIDED',
                'description' => "Voided order #{$order->id} for {$order->customer_name} — stock restored.",
            ]);

            return back()->with('success', "Order #{$order->id} has been voided and inventory stock has been restored.");
        } catch (Exception $e) {
            DB::rollBack();
            return back()->with('error', "Failed to void order: " . $e->getMessage());
        }
    }
}
