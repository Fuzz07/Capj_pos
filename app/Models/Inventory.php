<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock_qty',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_qty' => 'integer',
        'is_active' => 'boolean',
    ];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query, $threshold = 5)
    {
        return $query->where('is_active', true)->where('stock_qty', '<=', $threshold);
    }
}
