<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a reusable discount preset for managing discount intent.
 *
 * Discounts are parameter-driven presets that reflect how dyers actually sell.
 * They manage discount intent in Fibermade and are executed by Shopify at checkout.
 * Supported types include: order threshold free shipping, quantity-based per-skein
 * discounts, percentage discounts, manual free shipping codes, and time-boxed sales.
 * Each Discount belongs to a User and can be synced with Shopify.
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $type
 * @property array $parameters
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property bool $active
 * @property string|null $shopify_discount_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Discount extends Model
{
    /** @use HasFactory<\Database\Factories\DiscountFactory> */
    use HasFactory;
}
