<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->unsignedInteger('plan_type');
            $table->string('status')->default('available');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->index(['plan_type', 'status']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
