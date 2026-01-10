<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Represents a creator account.
 *
 * Creators are dyers who own catalog items (Colorways, Bases, Collections).
 * Each Creator belongs to an Account and contains creator-specific contact
 * and business information.
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
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Creator extends Model
{
    /** @use HasFactory<\Database\Factories\CreatorFactory> */
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
        'phone',
        'address_line1',
        'address_line2',
        'city',
        'state_region',
        'postal_code',
        'country_code',
    ];

    /**
     * Get the account that this creator belongs to.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the stores that this creator sells to (vendor relationships).
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Store::class, 'creator_store')
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
