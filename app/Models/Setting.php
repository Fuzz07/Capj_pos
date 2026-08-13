<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Key/value application settings, editable by an administrator from
 * Admin Panel -> Settings. Values fall back to defaults() when unset,
 * so the app works on a fresh install with an empty table.
 */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /** Per-request cache so a page render costs at most one query. */
    private static ?array $loaded = null;

    /**
     * Every supported setting with its default value.
     */
    public static function defaults(): array
    {
        return [
            // Shop identity (printed on receipts)
            'shop_name' => 'CAPTAiN J',
            'shop_tagline' => 'POUR IT, SAVOR IT, LOVE IT',
            'shop_address' => '',
            'shop_contact' => '',
            'receipt_footer' => 'Thank you for your order!',

            // Sales & orders
            'takeout_fee_amount' => '5',
            'takeout_fee_per_items' => '2',

            // Payments
            'gcash_number' => config('pos.gcash.number', '09536774000'),
            'gcash_name' => 'CAPTAiN J',

            // Inventory
            'low_stock_threshold' => (string) config('pos.low_stock_threshold', 5),

            // Security
            'login_max_attempts' => '3',
            'login_lockout_minutes' => '10',
            'otp_expiry_minutes' => '10',
        ];
    }

    /**
     * Note: named loadAll() rather than load() — Eloquent already defines a
     * non-static load() and redeclaring it statically is a fatal error.
     */
    private static function loadAll(): array
    {
        if (self::$loaded !== null) {
            return self::$loaded;
        }

        $stored = [];

        // Tolerate the table not existing yet (e.g. before migrating)
        try {
            if (Schema::hasTable('settings')) {
                $stored = static::query()->pluck('value', 'key')->all();
            }
        } catch (\Throwable $e) {
            $stored = [];
        }

        return self::$loaded = array_merge(self::defaults(), array_filter(
            $stored,
            fn($v) => $v !== null
        ));
    }

    public static function get(string $key, $default = null)
    {
        $values = self::loadAll();
        return $values[$key] ?? $default ?? (self::defaults()[$key] ?? null);
    }

    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key);
        return is_numeric($value) ? (int) $value : $default;
    }

    public static function getFloat(string $key, float $default = 0.0): float
    {
        $value = self::get($key);
        return is_numeric($value) ? (float) $value : $default;
    }

    /** All settings merged over defaults — handy for forms and views. */
    public static function values(): array
    {
        return self::loadAll();
    }

    public static function put(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            static::updateOrCreate(
                ['key' => $key],
                ['value' => is_null($value) ? null : (string) $value]
            );
        }

        self::flush();
    }

    public static function flush(): void
    {
        self::$loaded = null;
    }
}
