<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mikrotik_users', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20)->unique();
            $table->string('username', 100)->unique();
            $table->string('profile')->nullable();
            $table->unsignedInteger('plan_type')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mikrotik_users');
    }
};
