<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateObservationsTable extends Migration
{
    public function up(): void
    {
        Schema::create('observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_id')->constrained()->onDelete('cascade');
            $table->string('provider');
            $table->timestamp('observed_at');
            $table->json('normalized_data')->nullable();
            $table->json('raw_data')->nullable();
            $table->string('content_hash')->nullable();
            $table->string('status')->default('success');
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('observations');
    }
}
