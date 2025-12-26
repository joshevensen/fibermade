<?php

namespace App\Models;

use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a yarn colorway (unique color pattern) in the catalog.
 *
 * Colorways are a core part of catalog awareness in Stage 1, allowing dyers to
 * manage their yarn color patterns. Each colorway belongs to an Account and can
 * be associated with Bases (through Inventory) and Collections.
 * Colorways support status tracking (active, retired) and can be synced with
 * Shopify products.
 *
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \App\Enums\Technique|null $technique
 * @property \Illuminate\Support\Collection<int, \App\Enums\Color>|null $colors
 * @property \App\Enums\ColorwayStatus $status
 * @property string|null $shopify_product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Colorway extends Model
{
    /** @use HasFactory<\Database\Factories\ColorwayFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ColorwayStatus::class,
            'technique' => Technique::class,
            'colors' => AsEnumCollection::class.':'.Color::class,
        ];
    }
}
