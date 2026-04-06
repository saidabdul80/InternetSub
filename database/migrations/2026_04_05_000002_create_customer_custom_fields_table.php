<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_custom_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('field_name');
            $table->string('field_value');
            $table->timestamps();

            $table->index(['customer_id', 'field_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_custom_fields');
    }
};
