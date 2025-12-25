<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents an item within an order.
 *
 * OrderItems specify the Colorway, Base, and quantity for each line item in
 * an Order. They can optionally link to an Inventory entry for reservation
 * tracking and include a price for financial calculations.
 *
 * @property int $id
 * @property int $order_id
 * @property int $colorway_id
 * @property int $base_id
 * @property int $quantity
 * @property float|null $price
 * @property int|null $inventory_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;
}
