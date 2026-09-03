<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','preparing','shipping','delivered','cancelled','refunded') DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::table('orders')->where('status', 'refunded')->update(['status' => 'cancelled']);
            DB::statement("ALTER TABLE orders MODIFY status ENUM('pending','preparing','shipping','delivered','cancelled') DEFAULT 'pending'");
        }
    }
};
