<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetSyncDataCommand extends Command
{
    protected $signature = 'db:reset-data';

    protected $description = 'Truncate all synced/product data while preserving accounts, users, integrations, and billing. Not available in production.';

    /**
     * Tables to truncate, in order (child tables before parents to avoid FK issues).
     *
     * @var array<int, string>
     */
    protected array $tables = [
        'colorway_collection',
        'colorway_dye',
        'creator_store',
        'account_vendor_relationships',
        'order_items',
        'inventories',
        'colorways',
        'bases',
        'collections',
        'dyes',
        'orders',
        'customers',
        'shows',
        'stores',
        'invites',
        'integration_logs',
        'media',
        'external_identifiers',
    ];

    public function handle(): int
    {
        if (config('app.env') === 'production') {
            $this->error('This command cannot be run in production.');

            return self::FAILURE;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($this->tables as $table) {
            DB::table($table)->truncate();
            $this->line("  Truncated: {$table}");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Sync data reset complete.');

        return self::SUCCESS;
    }
}
