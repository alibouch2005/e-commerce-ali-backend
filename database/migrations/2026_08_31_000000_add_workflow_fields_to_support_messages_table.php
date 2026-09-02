<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->foreignId('answered_by')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $table->string('priority')->default('normal')->after('message');
            $table->text('admin_reply')->nullable()->after('status');
            $table->timestamp('answered_at')->nullable()->after('admin_reply');
            $table->timestamp('closed_at')->nullable()->after('answered_at');
            $table->index(['user_id', 'status']);
            $table->index(['priority', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('support_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('answered_by');
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['priority', 'status']);
            $table->dropColumn(['priority', 'admin_reply', 'answered_at', 'closed_at']);
        });
    }
};
