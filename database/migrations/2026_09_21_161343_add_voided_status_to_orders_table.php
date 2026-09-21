<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL requires redefining the full ENUM list to add a new value
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'completed', 'cancelled', 'voided') NOT NULL DEFAULT 'completed'");
    }

    public function down(): void
    {
        // Revert 'voided' rows to 'cancelled' before removing the value
        DB::statement("UPDATE `orders` SET `status` = 'cancelled' WHERE `status` = 'voided'");
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `status` ENUM('pending', 'completed', 'cancelled') NOT NULL DEFAULT 'completed'");
    }
};
