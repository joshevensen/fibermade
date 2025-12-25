<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents production-aware inventory tracking for colorway + base combinations.
 *
 * Inventory maintains the truth about physical yarn quantities. It tracks both
 * total quantity and reserved quantity (for orders that haven't been reconciled).
 * Inventory reflects production reality and is the system of record for inventory
 * truth, while Shopify receives availability values (quantity - reserved_quantity).
 * Each Inventory entry represents a unique Colorway + Base combination.
 *
 * @property int $id
 * @property int $colorway_id
 * @property int $base_id
 * @property int $quantity
 * @property int $reserved_quantity
 * @property string|null $shopify_variant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Inventory extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryFactory> */
    use HasFactory;
}
