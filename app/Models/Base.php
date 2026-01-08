<?php

namespace App\Models;

use App\Enums\BaseStatus;
use App\Enums\Weight;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents a yarn base/type (different yarn materials) in the catalog.
 *
 * Bases are yarn material types (e.g., merino wool, alpaca, etc.) that can be
 * combined with Colorways through Inventory to create specific yarn products.
 * Each Base belongs to an Account and supports status tracking (active, retired).
 *
 * @property int $id
 * @property int $account_id
 * @property string|null $description
 * @property \App\Enums\BaseStatus $status
 * @property \App\Enums\Weight|null $weight
 * @property string $descriptor
 * @property string|null $code
 * @property int|null $size
 * @property float|null $cost
 * @property float|null $retail_price
 * @property float|null $wool_percent
 * @property float|null $nylon_percent
 * @property float|null $alpaca_percent
 * @property float|null $yak_percent
 * @property float|null $camel_percent
 * @property float|null $cotton_percent
 * @property float|null $bamboo_percent
 * @property float|null $silk_percent
 * @property float|null $linen_percent
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Base extends Model
{
    /** @use HasFactory<\Database\Factories\BaseFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'description',
        'status',
        'weight',
        'descriptor',
        'code',
        'size',
        'cost',
        'retail_price',
        'wool_percent',
        'nylon_percent',
        'alpaca_percent',
        'yak_percent',
        'camel_percent',
        'cotton_percent',
        'bamboo_percent',
        'silk_percent',
        'linen_percent',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Base $base): void {
            if (empty($base->code) && ! empty($base->descriptor)) {
                $base->code = static::generateCodeFromDescriptor($base->descriptor);
            }
        });
    }

    /**
     * Generate a code from the descriptor by taking initials.
     */
    public static function generateCodeFromDescriptor(string $descriptor): string
    {
        $words = explode(' ', trim($descriptor));

        if (count($words) === 0) {
            return '';
        }

        if (count($words) === 1) {
            return strtoupper(substr($words[0], 0, 1));
        }

        $initials = '';
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BaseStatus::class,
            'weight' => Weight::class,
            'size' => 'integer',
            'cost' => 'decimal:2',
            'retail_price' => 'decimal:2',
            'wool_percent' => 'decimal:2',
            'nylon_percent' => 'decimal:2',
            'alpaca_percent' => 'decimal:2',
            'yak_percent' => 'decimal:2',
            'camel_percent' => 'decimal:2',
            'cotton_percent' => 'decimal:2',
            'bamboo_percent' => 'decimal:2',
            'silk_percent' => 'decimal:2',
            'linen_percent' => 'decimal:2',
        ];
    }

    /**
     * Get the account that owns this base.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the inventory entries for this base.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the order items for this base.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
