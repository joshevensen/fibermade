<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents a customer who can place orders.
 *
 * Customers belong to an Account and contain contact information including
 * name, email, phone, address details, and optional notes.
 *
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address_line1
 * @property string|null $address_line2
 * @property string|null $city
 * @property string|null $state_region
 * @property string|null $postal_code
 * @property string|null $country_code
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'email',
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state_region',
        'postal_code',
        'country_code',
        'notes',
    ];

    /**
     * Get the account that owns this customer.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all orders for this customer.
     */
    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    /**
     * Get the external identifiers for this customer.
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
     * Check if this customer has an external ID for the given integration and type.
     */
    public function hasExternalId(Integration $integration, string $externalType): bool
    {
        return $this->externalIdentifiers()
            ->where('integration_id', $integration->id)
            ->where('external_type', $externalType)
            ->exists();
    }
}
