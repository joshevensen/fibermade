<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            'email' => 'josh@fibermade.app',
            'is_admin' => true,
            'password' => Hash::make('password'),
        ]);

        // Bad Frog Yarn Co. users (will be associated with account in AccountSeeder)
        User::factory()->create([
            'name' => 'Josh Evensen',
            'email' => 'josh@badfrogyarnco.com',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'Kristen Matte',
            'email' => 'kristen@badfrogyarnco.com',
            'password' => Hash::make('password'),
        ]);

        // Yarnivore users (will be associated with account in AccountSeeder)
        User::factory()->create([
            'name' => 'Caryn',
            'email' => 'caryn@yarnivoresa.net',
            'password' => Hash::make('password'),
        ]);

        User::factory()->create([
            'name' => 'Han Smith',
            'email' => 'han@yarnivoresa.net',
            'password' => Hash::make('password'),
        ]);
    }
}
