<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

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
            abort(403, 'Unauthorized action.');
        }
        $order->items()->delete();
        $order->delete();
        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }
}
