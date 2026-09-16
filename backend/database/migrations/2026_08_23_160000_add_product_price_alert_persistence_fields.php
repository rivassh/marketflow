<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProductPriceAlertPersistenceFields extends Migration
{
    public function up(): void
    {
        Schema::table('product_price_alerts', function (Blueprint $table) {
            $table->integer('redis_ttl')->nullable()->comment('Redis cache TTL in seconds');
            $table->timestamp('last_notified_at')->nullable()->comment('Last notification timestamp');
            $table->json('alert_conditions')->nullable()->comment('JSON array of alert conditions');
        });
    }

    public function down(): void
    {
        Schema::table('product_price_alerts', function (Blueprint $table) {
            $table->dropColumn(['redis_ttl', 'last_notified_at', 'alert_conditions']);
        });
    }
}