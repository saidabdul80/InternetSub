<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->string('invoice')->unique();
            $table->string('username');
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('plan_name');
            $table->decimal('price', 15, 2);
            $table->timestamp('recharged_at');
            $table->timestamp('expires_at')->nullable();
            $table->string('method');
            $table->foreignId('router_id')->nullable()->constrained('network_routers')->nullOnDelete();
            $table->string('router_name')->default('');
            $table->string('service_type', 20);
            $table->string('note')->default('');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['username', 'created_at']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
