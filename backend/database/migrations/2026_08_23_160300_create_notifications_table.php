<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNotificationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('watch_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('alert_event_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('channel')->default('sms');
            $table->string('recipient');
            $table->string('template')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('pending');
            $table->string('provider_message_id')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('watch_id');
            $table->index('alert_event_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
}