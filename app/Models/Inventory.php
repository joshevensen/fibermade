<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents production-aware inventory tracking for colorway + base combinations.
 *
 * Inventory maintains the truth about physical yarn quantities. It tracks total
 * quantity for each Colorway + Base combination. Inventory reflects production
 * reality and is the system of record for inventory truth, while Shopify receives
 * availability values. Each Inventory entry represents a unique Colorway + Base combination.
 *
 * @property int $id
 * @property int $colorway_id
 * @property int $base_id
 * @property int $quantity
 * @property string|null $shopify_variant_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Inventory extends Model
{
    /** @use HasFactory<\Database\Factories\InventoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'colorway_id',
        'base_id',
        'quantity',
        'shopify_variant_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * Get the colorway for this inventory entry.
     */
    public function colorway(): BelongsTo
    {
        return $this->belongsTo(Colorway::class);
    }

    /**
     * Get the base for this inventory entry.
     */
    public function base(): BelongsTo
    {
        return $this->belongsTo(Base::class);
    }
}
