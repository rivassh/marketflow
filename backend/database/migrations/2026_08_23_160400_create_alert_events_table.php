<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlertEventsTable extends Migration
{
    public function up(): void
    {
        Schema::create('alert_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_id')->constrained()->onDelete('cascade');
            $table->foreignId('observation_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('event_key')->nullable();
            $table->string('state')->default('condition_not_met');
            $table->timestamp('triggered_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['watch_id', 'event_key']);
            $table->index('state');
            $table->index('event_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_events');
    }
}
