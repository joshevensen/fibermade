<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->truncateTables();

        $this->call([
            FoundationSeeder::class,
            BadFrogSeeder::class,
            // OrdersSeeder::class,
        ]);
    }

    /**
     * Truncate all tables except migrations.
     */
    protected function truncateTables(): void
    {
        $tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");

        $tableNames = array_filter(
            array_map(fn ($table) => $table->tablename, $tables),
            fn ($tableName) => $tableName !== 'migrations'
        );

        if (empty($tableNames)) {
            return;
        }

        $quotedTableNames = array_map(fn ($name) => "\"{$name}\"", $tableNames);
        $tableList = implode(', ', $quotedTableNames);

        DB::statement("TRUNCATE TABLE {$tableList} RESTART IDENTITY CASCADE");
    }
}
