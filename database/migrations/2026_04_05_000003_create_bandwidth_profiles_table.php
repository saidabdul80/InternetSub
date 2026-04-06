<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bandwidth_profiles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('rate_down');
            $table->enum('rate_down_unit', ['Kbps', 'Mbps']);
            $table->unsignedInteger('rate_up');
            $table->enum('rate_up_unit', ['Kbps', 'Mbps']);
            $table->string('burst')->default('');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bandwidth_profiles');
    }
};
