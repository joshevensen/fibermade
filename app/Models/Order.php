<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents a unified order for all order types (wholesale, retail, show).
 *
 * Orders serve as the intake mechanism for production planning. They reserve
 * inventory and contribute to dynamically generated Dye Lists. Orders can be
 * wholesale (external buyers), retail (paid Shopify orders imported for planning),
 * or show orders (internal allocation for in-person events). All order types
 * participate in production planning and inventory reservation.
 *
 * @property int $id
 * @property \App\Enums\OrderType $type
 * @property \App\Enums\OrderStatus $status
 * @property int $account_id
 * @property string|null $shopify_order_id
 * @property \Illuminate\Support\Carbon $order_date
 * @property float|null $subtotal_amount
 * @property float|null $shipping_amount
 * @property float|null $discount_amount
 * @property float|null $tax_amount
 * @property float|null $total_amount
 * @property string|null $notes
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'status',
        'account_id',
        'shopify_order_id',
        'order_date',
        'subtotal_amount',
        'shipping_amount',
        'discount_amount',
        'tax_amount',
        'total_amount',
        'notes',
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
            'type' => OrderType::class,
            'status' => OrderStatus::class,
            'order_date' => 'date',
            'subtotal_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * Get the account for this order.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the order items for this order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the user who created this order.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this order.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
