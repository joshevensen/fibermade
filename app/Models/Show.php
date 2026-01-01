<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents a show event such as a Trunk Show at a local yarn store, local market, or fiber festival.
 *
 * Shows are events where vendors can display and sell their products. Each Show belongs to an Account
 * and includes location information, dates, and optional description and website details.
 *
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property \Illuminate\Support\Carbon $start_at
 * @property \Illuminate\Support\Carbon $end_at
 * @property string|null $location_name
 * @property string|null $address_line1
 * @property string|null $city
 * @property string|null $state_region
 * @property string|null $postal_code
 * @property string|null $country_code
 * @property string|null $description
 * @property string|null $website
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Show extends Model
{
    /** @use HasFactory<\Database\Factories\ShowFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'start_at',
        'end_at',
        'location_name',
        'address_line1',
        'city',
        'state_region',
        'postal_code',
        'country_code',
        'description',
        'website',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
        ];
    }

    /**
     * Get the account that owns this show.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get all orders for this show.
     */
    public function orders(): MorphMany
    {
        return $this->morphMany(Order::class, 'orderable');
    }
}
