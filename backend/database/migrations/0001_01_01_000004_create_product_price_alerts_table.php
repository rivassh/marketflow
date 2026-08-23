<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductPriceAlertsTable extends Migration
{
    public function up(): void
    {
        Schema::create('product_price_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->integer('min_price'); // Price in minor units (e.g., cents)
            $table->integer('max_price'); // Price in minor units (e.g., cents)
            $table->string('currency', 3)->default('IRR');
            $table->string('availability');
            $table->string('stock_status');
            $table->string('seller_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_price_alerts');
    }
}