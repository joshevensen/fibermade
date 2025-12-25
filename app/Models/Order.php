<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
 * @property string $type
 * @property string $status
 * @property int|null $account_id
 * @property int $user_id
 * @property string|null $shopify_order_id
 * @property \Illuminate\Support\Carbon $order_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;
}
