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
        Schema::create('external_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();
            $table->string('identifiable_type');
            $table->unsignedBigInteger('identifiable_id');
            $table->string('external_type'); // 'product', 'variant', 'order', 'discount', 'customer', etc.
            $table->string('external_id'); // provider's ID
            $table->json('data')->nullable(); // admin URLs, raw payload, metadata
            $table->timestamps();

            // Prevent duplicate external IDs across integrations
            $table->unique(['integration_id', 'external_type', 'external_id']);

            // Prevent multiple external IDs of same type for same model+integration
            $table->unique(['integration_id', 'identifiable_type', 'identifiable_id', 'external_type']);

            // Index for polymorphic lookups
            $table->index(['identifiable_type', 'identifiable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_identifiers');
    }
};
