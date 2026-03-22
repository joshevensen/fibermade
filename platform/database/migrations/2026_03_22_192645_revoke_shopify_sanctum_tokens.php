<?php

use Illuminate\Database\Migrations\Migration;
use Laravel\Sanctum\PersonalAccessToken;

return new class extends Migration
{
    public function up(): void
    {
        PersonalAccessToken::where('name', 'shopify')->delete();
    }

    public function down(): void
    {
        // Intentionally irreversible — revoked tokens cannot be restored.
    }
};
