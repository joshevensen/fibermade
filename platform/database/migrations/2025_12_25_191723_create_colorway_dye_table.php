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
        Schema::create('colorway_dye', function (Blueprint $table) {
            $table->id();
            $table->foreignId('colorway_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dye_id')->constrained()->cascadeOnDelete();
            $table->decimal('dry_weight', 8, 2)->nullable();
            $table->decimal('concentration', 8, 2)->nullable();
            $table->decimal('wet_amount', 8, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['colorway_id', 'dye_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colorway_dye');
    }
};
