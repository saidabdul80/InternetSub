<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table): void {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->string('photo')->default('/user.default.jpg');
            $table->string('pppoe_username')->default('');
            $table->string('pppoe_password')->default('');
            $table->string('pppoe_ip')->default('');
            $table->string('full_name');
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('district')->nullable();
            $table->string('state')->nullable();
            $table->string('zip', 20)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('coordinates', 50)->default('');
            $table->enum('account_type', ['Business', 'Personal'])->default('Personal');
            $table->decimal('balance', 15, 2)->default(0);
            $table->enum('service_type', ['Hotspot', 'PPPoE', 'Others'])->default('Others');
            $table->boolean('auto_renewal')->default(true);
            $table->enum('status', ['Active', 'Banned', 'Disabled', 'Inactive', 'Limited', 'Suspended'])->default('Active');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'service_type']);
            $table->index('phone_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
