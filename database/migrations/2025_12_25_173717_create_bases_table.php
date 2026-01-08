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
        Schema::create('bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->string('weight')->nullable();
            $table->string('descriptor');
            $table->string('code')->nullable();
            $table->integer('size')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('retail_price', 10, 2)->nullable();
            $table->decimal('wool_percent', 5, 2)->nullable();
            $table->decimal('nylon_percent', 5, 2)->nullable();
            $table->decimal('alpaca_percent', 5, 2)->nullable();
            $table->decimal('yak_percent', 5, 2)->nullable();
            $table->decimal('camel_percent', 5, 2)->nullable();
            $table->decimal('cotton_percent', 5, 2)->nullable();
            $table->decimal('bamboo_percent', 5, 2)->nullable();
            $table->decimal('silk_percent', 5, 2)->nullable();
            $table->decimal('linen_percent', 5, 2)->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index('status');
            $table->index('weight');
            $table->index('code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bases');
    }
};
