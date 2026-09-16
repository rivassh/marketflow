<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWatchesTable extends Migration
{
    public function up(): void
    {
        Schema::create('watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('product_url');
            $table->string('product_ref')->nullable();
            $table->string('workflow_type')->default('PRICE_BELOW_ALERT');
            $table->json('conditions')->nullable();
            $table->integer('frequency_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_tracked')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->integer('alert_count')->default(0);
            $table->json('price_history')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watches');
    }
}