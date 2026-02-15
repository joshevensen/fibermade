<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Represents production-aware inventory tracking for colorway + base combinations.
 *
 * Inventory maintains the truth about physical yarn quantities. It tracks total
 * quantity for each Colorway + Base combination. Inventory reflects production
 * reality and is the system of record for inventory truth, while Shopify receives
 * availability values. Each Inventory entry belongs to an Account and represents a unique Colorway + Base combination.
 *
 * @property int $id
 * @property int $account_id
 * @property int $colorway_id
 * @property int $base_id
 * @property int $quantity
 * @property \Illuminate\Support\Carbon|null $last_synced_at
 * @property string|null $sync_status
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
        'account_id',
        'colorway_id',
        'base_id',
        'quantity',
        'last_synced_at',
        'sync_status',
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
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the account that owns this inventory entry.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
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

    /**
     * Get the external identifiers for this inventory entry.
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
     * Check if this inventory entry has an external ID for the given integration and type.
     */
    public function hasExternalId(Integration $integration, string $externalType): bool
    {
        return $this->externalIdentifiers()
            ->where('integration_id', $integration->id)
            ->where('external_type', $externalType)
            ->exists();
    }
}
