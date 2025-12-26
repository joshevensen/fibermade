<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents an item within an order.
 *
 * OrderItems specify the Colorway, Base, and quantity for each line item in
 * an Order. They include pricing information for financial calculations.
 *
 * @property int $id
 * @property int $order_id
 * @property int $colorway_id
 * @property int $base_id
 * @property int $quantity
 * @property float|null $unit_price
 * @property float|null $line_total
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'colorway_id',
        'base_id',
        'quantity',
        'unit_price',
        'line_total',
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
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    /**
     * Get the order that owns this order item.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the colorway for this order item.
     */
    public function colorway(): BelongsTo
    {
        return $this->belongsTo(Colorway::class);
    }

    /**
     * Get the base for this order item.
     */
    public function base(): BelongsTo
    {
        return $this->belongsTo(Base::class);
    }
}
