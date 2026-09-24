<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Order;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class OrderController extends Controller
{
    private function ensureSoftDeletesColumn()
    {
        try {
            if (!Schema::hasColumn('orders', 'deleted_at')) {
                Schema::table('orders', function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        } catch (\Throwable $th) {
            // Ignore if column exists or schema alter fails
        }
    }

    public function index(Request $request)
    {
        $this->ensureSoftDeletesColumn();
        $tab = $request->query('tab', 'active');

        if ($tab === 'archived') {
            $orders = Order::onlyTrashed()->with('user', 'items.inventory')->latest()->paginate(15);
        } else {
            $orders = Order::with('user', 'items.inventory')->latest()->paginate(15);
        }

        $activeCount = Order::count();
        $archivedCount = Order::onlyTrashed()->count();

        return view('orders.index', compact('orders', 'tab', 'activeCount', 'archivedCount'));
    }

    public function show($id)
    {
        $this->ensureSoftDeletesColumn();
        $order = Order::withTrashed()->with('user', 'items.inventory')->findOrFail($id);
        return view('orders.show', compact('order'));
    }

    /**
     * Archive an order (Soft Delete).
     */
    public function destroy(Order $order)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('orders.index')->with('error', 'Access denied. Only administrators can archive orders.');
        }

        $this->ensureSoftDeletesColumn();
        $orderId = $order->id;
        $order->delete();

        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'ORDER_ARCHIVED',
            'description' => "Archived order #{$orderId}.",
        ]);

        return redirect()->route('orders.index')->with('success', "Order #{$orderId} has been archived.");
    }

    /**
     * Restore an archived order (Unarchive).
     */
    public function restore($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('orders.index')->with('error', 'Access denied. Only administrators can unarchive orders.');
        }

        $this->ensureSoftDeletesColumn();
        $order = Order::onlyTrashed()->findOrFail($id);
        $order->restore();

        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'ORDER_UNARCHIVED',
            'description' => "Unarchived order #{$id}.",
        ]);

        return redirect()->route('orders.index', ['tab' => 'archived'])->with('success', "Order #{$id} has been restored from archive.");
    }

    /**
     * Permanently delete an order from archive.
     */
    public function forceDelete($id)
    {
        if (auth()->user()->role !== 'admin') {
            return redirect()->route('orders.index')->with('error', 'Access denied. Only administrators can permanently delete orders.');
        }

        $this->ensureSoftDeletesColumn();
        $order = Order::onlyTrashed()->findOrFail($id);
        $order->items()->delete();
        $order->forceDelete();

        ActivityLog::create([
            'user_id'     => auth()->id(),
            'action'      => 'ORDER_PERMANENTLY_DELETED',
            'description' => "Permanently deleted order #{$id} from archive.",
        ]);

        return redirect()->route('orders.index', ['tab' => 'archived'])->with('success', "Order #{$id} has been permanently deleted.");
    }

    /**
     * Void a completed order (accessible by Admin and Staff).
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
