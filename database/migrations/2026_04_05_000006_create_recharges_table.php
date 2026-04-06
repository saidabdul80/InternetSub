<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recharges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('username');
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('plan_name');
            $table->timestamp('recharged_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 20);
            $table->string('method')->default('');
            $table->foreignId('router_id')->nullable()->constrained('network_routers')->nullOnDelete();
            $table->string('router_name')->default('');
            $table->string('service_type', 20);
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'status']);
            $table->index(['username', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recharges');
    }
};
