<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('short_description', 500)->nullable()->after('description');
            $table->text('long_description')->nullable()->after('short_description');
        });

        DB::table('products')->whereNull('short_description')->update([
            'short_description' => DB::raw('description'),
            'long_description' => DB::raw('description'),
        ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['short_description', 'long_description']);
        });
    }
};
