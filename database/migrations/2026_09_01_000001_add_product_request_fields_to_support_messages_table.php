<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->string('type')->default('support')->after('user_id');
            $table->string('requested_product_name')->nullable()->after('subject');
            $table->string('requested_product_image')->nullable()->after('requested_product_name');
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropIndex(['type', 'status']);
            $table->dropColumn(['type', 'requested_product_name', 'requested_product_image']);
        });
    }
};
