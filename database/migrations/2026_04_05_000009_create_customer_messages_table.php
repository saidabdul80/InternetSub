<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->string('subject');
            $table->text('body')->nullable();
            $table->string('from_type', 20)->default('System');
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['customer_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_messages');
    }
};
