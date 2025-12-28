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
        Schema::create('dyes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('does_bleed')->default(false);
            $table->boolean('do_like')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index('does_bleed');
            $table->index('do_like');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dyes');
    }
};
