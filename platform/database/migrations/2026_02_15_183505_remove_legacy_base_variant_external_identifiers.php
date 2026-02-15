<?php

use App\Models\Base;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('external_identifiers')
            ->where('identifiable_type', Base::class)
            ->where('external_type', 'shopify_variant')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot restore deleted records.
    }
};
