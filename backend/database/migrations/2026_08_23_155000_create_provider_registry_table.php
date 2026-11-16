<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProviderRegistryTable extends Migration
{
    public function up(): void
    {
        Schema::create('provider_registry', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('configuration');
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_registry');
    }
}
