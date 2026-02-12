<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('orders')->where('status', 'closed')->update(['status' => 'delivered']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('orders')->where('status', 'delivered')->update(['status' => 'closed']);
    }
};
