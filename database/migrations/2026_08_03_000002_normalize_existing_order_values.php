<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep existing installations compatible with the stable API values.
        foreach ([
            'En attente' => 'pending', 'Préparation' => 'preparing', 'PrÃ©paration' => 'preparing',
            'Expédié' => 'shipping', 'ExpÃ©diÃ©' => 'shipping', 'Livré' => 'delivered', 'LivrÃ©' => 'delivered',
        ] as $from => $to) {
            DB::table('orders')->where('status', $from)->update(['status' => $to]);
        }
        DB::table('orders')->where('payment_method', 'cash')->update(['payment_method' => 'cash_on_delivery']);

        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cash_on_delivery', 'card'])->default('cash_on_delivery')->change();
            $table->enum('status', ['pending', 'preparing', 'shipping', 'delivered', 'cancelled'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'card', 'cash_on_delivery'])->change();
            $table->enum('status', ['En attente', 'Préparation', 'Expédié', 'Livré'])->default('En attente')->change();
        });
    }
};
