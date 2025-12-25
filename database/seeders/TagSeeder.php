<?php

namespace Database\Seeders;

use App\Enums\TagType;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $techniqueTags = [
            'Solid',
            'Tonal',
            'Variegated',
            'Speckled',
            'Other',
        ];

        foreach ($techniqueTags as $tagName) {
            Tag::updateOrCreate(
                ['slug' => Str::slug($tagName)],
                [
                    'type' => TagType::YarnTechnique->value,
                    'name' => $tagName,
                    'is_active' => true,
                ]
            );
        }

        $colorTags = [
            'Red',
            'Orange',
            'Yellow',
            'Green',
            'Blue',
            'Purple',
            'Pink',
            'Brown',
            'Black',
            'White',
            'Gray',
            'Teal',
            'Maroon',
            'Navy',
            'Beige',
            'Tan',
            'Coral',
            'Turquoise',
        ];

        foreach ($colorTags as $tagName) {
            Tag::updateOrCreate(
                ['slug' => Str::slug($tagName)],
                [
                    'type' => TagType::YarnColor->value,
                    'name' => $tagName,
                    'is_active' => true,
                ]
            );
        }
    }
}
