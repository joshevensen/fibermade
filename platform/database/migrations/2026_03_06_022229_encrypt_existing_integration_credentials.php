<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Encrypts any integration credentials that are still stored as plaintext
     * so they match the encrypted cast on the Integration model.
     */
    public function up(): void
    {
        $rows = DB::table('integrations')->get();

        foreach ($rows as $row) {
            try {
                Crypt::decryptString($row->credentials);
            } catch (DecryptException) {
                DB::table('integrations')
                    ->where('id', $row->id)
                    ->update(['credentials' => Crypt::encryptString($row->credentials)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * No-op: decrypting would require the same APP_KEY and cannot be safely reversed.
     */
    public function down(): void
    {
        //
    }
};
