<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->uuid('shopify_connect_token')->nullable()->unique()->after('type');
        });

        Account::whereNull('shopify_connect_token')->each(function (Account $account) {
            $account->update(['shopify_connect_token' => (string) Str::uuid()]);
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->uuid('shopify_connect_token')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('shopify_connect_token');
        });
    }
};
