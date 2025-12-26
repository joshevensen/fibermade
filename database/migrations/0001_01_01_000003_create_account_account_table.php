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
        Schema::create('store_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('discount_rate', 10, 2)->nullable();
            $table->integer('minimum_order_quantity')->nullable();
            $table->decimal('minimum_order_value', 10, 2)->nullable();
            $table->string('payment_terms')->nullable();
            $table->integer('lead_time_days')->nullable();
            $table->boolean('allows_preorders')->default(false);
            $table->string('status')->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->boolean('share_vendor_contact_info')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'vendor_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('store_vendor');
    }
};
