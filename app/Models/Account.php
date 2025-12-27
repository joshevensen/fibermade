<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;

/**
 * Represents an account that can be used by all account types (wholesale, retail, show).
 *
 * Accounts store buyer relationships and can represent wholesale buyers, retail
 * customers, or show organizers. Each Account has many Users through a pivot table
 * and owns catalog items (Colorways, Bases, Collections). Accounts support account-level
 * pricing rules and are used for wholesale order management and relationship tracking.
 *
 * @property int $id
 * @property \App\Enums\AccountType $type
 * @property \App\Enums\BaseStatus $status
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address_line_1
 * @property string|null $address_line_2
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip
 * @property string|null $country
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use Billable, HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'status',
        'name',
        'email',
        'phone',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip',
        'country',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AccountType::class,
            'status' => BaseStatus::class,
        ];
    }

    /**
     * Get the users that belong to this account.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the bases for this account.
     */
    public function bases(): HasMany
    {
        return $this->hasMany(Base::class);
    }

    /**
     * Get the collections for this account.
     */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    /**
     * Get the colorways for this account.
     */
    public function colorways(): HasMany
    {
        return $this->hasMany(Colorway::class);
    }

    /**
     * Get the discounts for this account.
     */
    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    /**
     * Get the dyes for this account.
     */
    public function dyes(): HasMany
    {
        return $this->hasMany(Dye::class);
    }

    /**
     * Get the orders for this account.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the integrations for this account.
     */
    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    /**
     * Get the inventories for this account.
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Get the vendors associated with this store account.
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'store_vendor', 'store_id', 'vendor_id')
            ->withPivot([
                'discount_rate',
                'minimum_order_quantity',
                'minimum_order_value',
                'payment_terms',
                'lead_time_days',
                'allows_preorders',
                'status',
                'started_at',
                'ended_at',
                'share_vendor_contact_info',
                'notes',
            ])
            ->withTimestamps();
    }

    /**
     * Get the stores associated with this vendor account.
     */
    public function stores(): BelongsToMany
    {
        return $this->belongsToMany(Account::class, 'store_vendor', 'vendor_id', 'store_id')
            ->withPivot([
                'discount_rate',
                'minimum_order_quantity',
                'minimum_order_value',
                'payment_terms',
                'lead_time_days',
                'allows_preorders',
                'status',
                'started_at',
                'ended_at',
                'share_vendor_contact_info',
                'notes',
            ])
            ->withTimestamps();
    }
}
