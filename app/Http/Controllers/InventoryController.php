<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\ActivityLog;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        NotificationService::checkAndNotifyLowStock();

        $query = Inventory::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'low') {
                $query->lowStock(config('pos.low_stock_threshold', 5));
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($request->status === 'active') {
                $query->where('is_active', true);
            }
        }

        $items = $query->orderBy('name')->paginate(15);

        return view('inventory.index', compact('items'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_qty' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $item = Inventory::create($validated);
        NotificationService::checkAndNotifyLowStock($item);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'INVENTORY_CREATED',
            'description' => "Created new inventory item: {$item->name}"
        ]);

        return redirect()->route('inventory.index')->with('success', 'Inventory item created successfully!');
    }

    public function update(Request $request, Inventory $inventory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock_qty' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $inventory->update($validated);
        NotificationService::checkAndNotifyLowStock($inventory);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'INVENTORY_UPDATED',
            'description' => "Updated inventory item: {$inventory->name}"
        ]);

        return redirect()->route('inventory.index')->with('success', 'Inventory item updated successfully!');
    }

    public function addStock(Request $request, Inventory $inventory)
    {
        $request->validate([
            'add_qty' => 'required|integer|min:1',
        ]);

        $inventory->increment('stock_qty', $request->add_qty);
        NotificationService::checkAndNotifyLowStock($inventory);

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'STOCK_ADDED',
            'description' => "Added {$request->add_qty} unit(s) of stock to '{$inventory->name}'."
        ]);

        return back()->with('success', "Added {$request->add_qty} unit(s) of stock to '{$inventory->name}'.");
    }

    public function destroy(Inventory $inventory)
    {
        $name = $inventory->name;
        $inventory->delete();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => 'INVENTORY_DELETED',
            'description' => "Deleted inventory item: {$name}"
        ]);

        return redirect()->route('inventory.index')->with('success', 'Inventory item deleted.');
    }
}
