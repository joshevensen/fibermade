<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Admin user (no account)
        User::factory()->create([
            'name' => 'Josh Evensen',
            'email' => 'josh@fibermade.com',
            'is_admin' => true,
        ]);

        // Bad Frog Yarn Co. users (will be associated with account in AccountSeeder)
        User::factory()->create([
            'name' => 'Josh Evensen',
            'email' => 'josh@badfrogyarnco.com',
            'is_admin' => false,
        ]);

        User::factory()->create([
            'name' => 'Kristen Matte',
            'email' => 'kristen@badfrogyarnco.com',
            'is_admin' => false,
        ]);

        // Yarnivore users (will be associated with account in AccountSeeder)
        User::factory()->create([
            'name' => 'Caryn',
            'email' => 'caryn@yarnivoresa.net',
            'is_admin' => false,
        ]);

        User::factory()->create([
            'name' => 'Han Smith',
            'email' => 'han@yarnivoresa.net',
            'is_admin' => false,
        ]);
    }
}
