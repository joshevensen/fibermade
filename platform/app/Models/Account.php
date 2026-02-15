<?php

namespace App\Models;

use App\Enums\AccountType;
use App\Enums\BaseStatus;
use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;

/**
 * Represents a generic account for user/account management.
 *
 * Accounts can be of different types (Creator, Store, Buyer) and serve as
 * the base for user management and account-level settings. Type-specific
 * data is stored in the respective tables (creators, stores, etc.).
 *
 * @property int $id
 * @property \App\Enums\BaseStatus $status
 * @property \App\Enums\AccountType $type
 * @property \Illuminate\Support\Carbon|null $onboarded_at
 * @property \App\Enums\SubscriptionStatus|null $subscription_status
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
        'status',
        'type',
        'subscription_status',
        'onboarded_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => BaseStatus::class,
            'type' => AccountType::class,
            'subscription_status' => SubscriptionStatus::class,
            'onboarded_at' => 'datetime',
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
     * Get the shows for this account.
     */
    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }

    /**
     * Get the creator for this account.
     */
    public function creator(): HasOne
    {
        return $this->hasOne(Creator::class);
    }

    /**
     * Get the store for this account.
     */
    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    /**
     * Whether this account type requires an active subscription to access Creator features.
     */
    public function requiresSubscription(): bool
    {
        return $this->type === AccountType::Creator;
    }

    /**
     * Whether the account has an active or grace-period subscription (allowed to access Creator routes).
     */
    public function hasActiveSubscription(): bool
    {
        if (! $this->requiresSubscription()) {
            return true;
        }

        return $this->subscription_status !== null && in_array($this->subscription_status, [
            SubscriptionStatus::Active,
            SubscriptionStatus::PastDue,
            SubscriptionStatus::Cancelled,
        ], true);
    }
}
