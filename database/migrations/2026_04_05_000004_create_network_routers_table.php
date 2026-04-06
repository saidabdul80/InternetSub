<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_routers', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('ip_address');
            $table->string('username');
            $table->string('password');
            $table->string('description')->nullable();
            $table->string('coordinates', 50)->default('');
            $table->enum('status', ['Online', 'Offline'])->default('Online');
            $table->dateTime('last_seen_at')->nullable();
            $table->string('coverage', 16)->default('0');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_routers');
    }
};
