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
        Schema::create('account_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id_1')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('account_id_2')->constrained('accounts')->cascadeOnDelete();
            $table->decimal('discount_rate', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['account_id_1', 'account_id_2']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_account');
    }
};
