<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('watch_id')->nullable()->constrained()->onDelete('set null');
            $table->string('product_url', 2048);
            $table->json('product_ref')->nullable();
            $table->string('title')->nullable();
            $table->decimal('target_price', 15, 2)->nullable();
            $table->string('currency', 10)->default('IRR');
            $table->string('seller')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('purchase_intent_id')->nullable()->constrained()->onDelete('set null');
            $table->string('status', 30)->default('draft');
            $table->string('seller')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->string('currency', 10)->default('IRR');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('placed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('product_url', 2048)->nullable();
            $table->json('product_ref')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('quantity')->default(1);
            $table->string('currency', 10)->default('IRR');
            $table->timestamps();
        });

        Schema::create('purchase_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type', 100);
            $table->unsignedBigInteger('auditable_id');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action', 50);
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_audit_logs');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_intents');
    }
};
