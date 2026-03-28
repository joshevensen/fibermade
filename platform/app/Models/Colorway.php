<?php

namespace App\Models;

use App\Enums\Color;
use App\Enums\ColorwayStatus;
use App\Enums\Technique;
use Database\Factories\ColorwayFactory;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
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
 * @property string|null $description
 * @property Technique|null $technique
 * @property \Illuminate\Support\Collection<int, Color>|null $colors
 * @property int $per_pan
 * @property string|null $recipe
 * @property string|null $notes
 * @property ColorwayStatus $status
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Colorway extends Model
{
    /** @use HasFactory<ColorwayFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'description',
        'technique',
        'colors',
        'per_pan',
        'recipe',
        'notes',
        'status',
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
        $media = $this->media()->where('is_primary', true)->first()
            ?? $this->media()->first();

        if (! $media) {
            return null;
        }

        return $this->resolveMediaUrl($media);
    }

    /**
     * Resolve the public URL for a media record.
     *
     * New records store a disk name + relative path. Legacy records (synced
     * before the disk column was added) may have an absolute URL in file_path.
     */
    public function resolveMediaUrl(Media $media): string
    {
        if ($media->disk) {
            return Storage::disk($media->disk)->url($media->file_path);
        }

        // Legacy: absolute URL stored directly in file_path
        if (str_starts_with($media->file_path, 'http')) {
            return $media->file_path;
        }

        return Storage::disk('public')->url($media->file_path);
    }

    /**
     * Get the external identifiers for this colorway.
     */
    public function externalIdentifiers(): MorphMany
    {
        return $this->morphMany(ExternalIdentifier::class, 'identifiable');
    }

    /**
     * Get external ID for a specific integration and external type.
     */
    public function getExternalIdFor(Integration $integration, string $externalType): ?string
    {
        $identifier = $this->externalIdentifiers()
            ->where('integration_id', $integration->id)
            ->where('external_type', $externalType)
            ->first();

        return $identifier?->external_id;
    }

    /**
     * Get all external IDs grouped by integration type.
     */
    public function getExternalIdsByIntegration(): array
    {
        return $this->externalIdentifiers()
            ->with('integration')
            ->get()
            ->groupBy(fn ($identifier) => $identifier->integration->type->value)
            ->map(fn ($identifiers) => $identifiers->keyBy('external_type')->map->external_id)
            ->toArray();
    }

    /**
     * Check if this colorway has an external ID for the given integration and type.
     */
    public function hasExternalId(Integration $integration, string $externalType): bool
    {
        return $this->externalIdentifiers()
            ->where('integration_id', $integration->id)
            ->where('external_type', $externalType)
            ->exists();
    }
}
