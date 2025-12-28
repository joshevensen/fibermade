<?php

namespace App\Models;

use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Colorway extends Model
{
    /** @use HasFactory<\Database\Factories\ColorwayFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'slug',
        'description',
        'technique',
        'colors',
        'status',
        'shopify_product_id',
        'created_by',
        'updated_by',
    ];

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

    /**
     * Get the account that owns this colorway.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the collections that contain this colorway.
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'colorway_collection')
            ->withTimestamps();
    }

    /**
     * Get the dyes used in this colorway.
     */
    public function dyes(): BelongsToMany
    {
        return $this->belongsToMany(Dye::class, 'colorway_dye')
            ->withPivot('dry_weight', 'concentration', 'wet_amount', 'notes')
            ->withTimestamps();
    }

    /**
     * Get the inventory entries for this colorway.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the order items for this colorway.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the user who created this colorway.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this colorway.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the media files for this colorway.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /**
     * Get the primary image URL for this colorway.
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primaryMedia = $this->media()->where('is_primary', true)->first();

        if ($primaryMedia) {
            return Storage::disk('public')->url($primaryMedia->file_path);
        }

        $firstMedia = $this->media()->first();

        if ($firstMedia) {
            return Storage::disk('public')->url($firstMedia->file_path);
        }

        return null;
    }
}
