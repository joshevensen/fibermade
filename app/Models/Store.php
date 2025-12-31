<?php

namespace App\Models;

use App\Enums\StoreVendorStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Represents a store that can place orders with creators.
 *
 * Stores have their own contact information, location, and vendor relationship
 * settings (discount rates, payment terms, etc.) that apply when ordering from creators.
 *
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string $email
 * @property string|null $owner_name
 * @property string $address_line_1
 * @property string|null $address_line_2
 * @property string $city
 * @property string $state
 * @property string $zip
 * @property string $country
 * @property float|null $discount_rate
 * @property int|null $minimum_order_quantity
 * @property float|null $minimum_order_value
 * @property string|null $payment_terms
 * @property int|null $lead_time_days
 * @property bool $allows_preorders
 * @property \App\Enums\StoreVendorStatus $status
 * @property string|null $notes
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
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip',
        'country',
        'discount_rate',
        'minimum_order_quantity',
        'minimum_order_value',
        'payment_terms',
        'lead_time_days',
        'allows_preorders',
        'status',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => StoreVendorStatus::class,
            'allows_preorders' => 'boolean',
            'discount_rate' => 'decimal:2',
            'minimum_order_value' => 'decimal:2',
        ];
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
}
