<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Represents an external identifier mapping for integration connections.
 *
 * Maps internal models (Order, Colorway, Inventory, Customer) to
 * external system identifiers (e.g., Shopify product IDs, order IDs, etc.).
 * Each identifier is tied to a specific integration connection and can store
 * additional metadata in the data JSON column.
 *
 * @property int $id
 * @property int $integration_id
 * @property string $identifiable_type
 * @property int $identifiable_id
 * @property string $external_type
 * @property string $external_id
 * @property array|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ExternalIdentifier extends Model
{
    /** @use HasFactory<\Database\Factories\ExternalIdentifierFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'integration_id',
        'identifiable_type',
        'identifiable_id',
        'external_type',
        'external_id',
        'data',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }

    /**
     * Get the integration that owns this external identifier.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Get the parent identifiable model (Order, Colorway, Inventory, Customer).
     */
    public function identifiable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope a query to only include external identifiers for a specific integration.
     */
    public function scopeForIntegration($query, Integration $integration)
    {
        return $query->where('integration_id', $integration->id);
    }

    /**
     * Scope a query to only include external identifiers of a specific type.
     */
    public function scopeOfType($query, string $externalType)
    {
        return $query->where('external_type', $externalType);
    }
}
