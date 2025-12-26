<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents a reusable discount preset for managing discount intent.
 *
 * Discounts are parameter-driven presets that reflect how dyers actually sell.
 * They manage discount intent in Fibermade and are executed by Shopify at checkout.
 * Supported types include: order threshold free shipping, quantity-based per-skein
 * discounts, percentage discounts, manual free shipping codes, and time-boxed sales.
 * Each Discount belongs to an Account and can be synced with Shopify.
 *
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property \App\Enums\DiscountType $type
 * @property string $code
 * @property array $parameters
 * @property \Illuminate\Support\Carbon|null $starts_at
 * @property \Illuminate\Support\Carbon|null $ends_at
 * @property bool $is_active
 * @property string|null $shopify_discount_id
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Discount extends Model
{
    /** @use HasFactory<\Database\Factories\DiscountFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'type',
        'code',
        'parameters',
        'starts_at',
        'ends_at',
        'is_active',
        'shopify_discount_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'parameters' => 'array',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the account that owns this discount.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
