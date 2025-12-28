<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Show;
use Illuminate\Database\Seeder;

class ShowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $account = Account::where('name', 'Bad Frog Yarn Co.')->first();

        if (! $account) {
            return;
        }

        $shows = [
            [
                'name' => 'Spring Fiber Festival',
                'start_at' => now()->addMonths(2)->startOfDay()->setTime(9, 0),
                'end_at' => now()->addMonths(2)->addDays(2)->endOfDay()->setTime(17, 0),
                'location_name' => 'Convention Center',
                'location_address' => '123 Main Street',
                'location_city' => 'Portland',
                'location_state' => 'OR',
                'location_zip' => '97201',
                'description' => 'Annual spring fiber festival featuring local yarn dyers, fiber artists, and workshops.',
                'website' => 'https://springfiberfestival.example.com',
            ],
            [
                'name' => 'Trunk Show at Local Yarn Store',
                'start_at' => now()->addWeeks(3)->setTime(10, 0),
                'end_at' => now()->addWeeks(3)->setTime(18, 0),
                'location_name' => 'Knit & Purl Yarn Shop',
                'location_address' => '456 Oak Avenue',
                'location_city' => 'Seattle',
                'location_state' => 'WA',
                'location_zip' => '98101',
                'description' => 'Exclusive trunk show featuring our latest colorways and bases.',
                'website' => null,
            ],
            [
                'name' => 'Downtown Yarn Market',
                'start_at' => now()->addMonths(4)->startOfDay()->setTime(8, 0),
                'end_at' => now()->addMonths(4)->endOfDay()->setTime(20, 0),
                'location_name' => 'Downtown Market Square',
                'location_address' => '789 Market Street',
                'location_city' => 'San Francisco',
                'location_state' => 'CA',
                'location_zip' => '94102',
                'description' => 'Outdoor yarn market with multiple vendors, food trucks, and live music.',
                'website' => 'https://downtownyarnmarket.example.com',
            ],
            [
                'name' => 'Fall Fiber Arts Fair',
                'start_at' => now()->addMonths(6)->startOfDay()->setTime(9, 0),
                'end_at' => now()->addMonths(6)->addDays(3)->endOfDay()->setTime(17, 0),
                'location_name' => 'Fairgrounds',
                'location_address' => '1000 Fairgrounds Road',
                'location_city' => 'Denver',
                'location_state' => 'CO',
                'location_zip' => '80202',
                'description' => 'Multi-day fiber arts fair with workshops, demonstrations, and vendor booths.',
                'website' => 'https://fallfiberartsfair.example.com',
            ],
            [
                'name' => 'Holiday Market',
                'start_at' => now()->addMonths(8)->startOfDay()->setTime(10, 0),
                'end_at' => now()->addMonths(8)->endOfDay()->setTime(16, 0),
                'location_name' => 'Community Center',
                'location_address' => '555 Elm Street',
                'location_city' => 'Boulder',
                'location_state' => 'CO',
                'location_zip' => '80301',
                'description' => 'Holiday market featuring hand-dyed yarns perfect for gift knitting.',
                'website' => null,
            ],
        ];

        foreach ($shows as $showData) {
            Show::create([
                'account_id' => $account->id,
                'name' => $showData['name'],
                'start_at' => $showData['start_at'],
                'end_at' => $showData['end_at'],
                'location_name' => $showData['location_name'],
                'location_address' => $showData['location_address'],
                'location_city' => $showData['location_city'],
                'location_state' => $showData['location_state'],
                'location_zip' => $showData['location_zip'],
                'description' => $showData['description'],
                'website' => $showData['website'],
            ]);
        }
    }
}
