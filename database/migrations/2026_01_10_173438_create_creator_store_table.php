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
        Schema::create('creator_store', function (Blueprint $table) {
            $table->id();
            $table->foreignId('creator_id')->constrained('creators')->cascadeOnDelete();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();

            // Relationship-specific fields (moved from stores table)
            $table->decimal('discount_rate', 10, 2)->nullable();
            $table->integer('minimum_order_quantity')->nullable();
            $table->decimal('minimum_order_value', 10, 2)->nullable();
            $table->string('payment_terms')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->boolean('allows_preorders')->default(false);
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['creator_id', 'store_id']);
            $table->index(['creator_id', 'status']);
            $table->index(['store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_store');
    }
};
