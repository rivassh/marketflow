<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            $table->timestamp('next_check_at')->nullable()->after('last_checked_at');
        });

        Schema::table('watches', function (Blueprint $table) {
            $table->index(['is_active', 'is_tracked', 'next_check_at'], 'watches_dispatch_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('watches', function (Blueprint $table) {
            $table->dropIndex('watches_dispatch_lookup_idx');
            $table->dropColumn('next_check_at');
        });
    }
};
