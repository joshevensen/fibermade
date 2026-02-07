<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Represents a store account that can place orders with creators.
 *
 * Stores have their own contact information and location.
 * Vendor relationship settings (discount rates, payment terms, allows_preorders, etc.) are stored
 * in the creator_store pivot table as they vary per relationship.
 *
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string $email
 * @property string|null $owner_name
 * @property string $address_line1
 * @property string|null $address_line2
 * @property string $city
 * @property string $state_region
 * @property string $postal_code
 * @property string $country_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Store extends Model
{
    /** @use HasFactory<\Database\Factories\StoreFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'email',
        'owner_name',
        'address_line1',
        'address_line2',
        'city',
        'state_region',
        'postal_code',
        'country_code',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [];
    }

    /**
     * Get the account that owns this store.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all orders for this store.
     */
    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    /**
     * Get the creators that this store buys from (vendor relationships).
     */
    public function creators(): BelongsToMany
    {
        return $this->belongsToMany(Creator::class, 'creator_store')
            ->withPivot([
                'discount_rate',
                'minimum_order_quantity',
                'minimum_order_value',
                'payment_terms',
                'lead_time_days',
                'allows_preorders',
                'status',
                'notes',
            ])
            ->withTimestamps();
    }
}
