<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->string('recipient_name')->nullable()->after('date_livraison');
            $table->text('delivery_note')->nullable()->after('recipient_name');
            $table->string('proof_image')->nullable()->after('delivery_note');
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', fn (Blueprint $table) => $table->dropColumn(['recipient_name', 'delivery_note', 'proof_image']));
    }
};
