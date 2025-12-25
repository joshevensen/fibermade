<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a yarn base/type (different yarn materials) in the catalog.
 *
 * Bases are yarn material types (e.g., merino wool, alpaca, etc.) that can be
 * combined with Colorways through Inventory to create specific yarn products.
 * Each Base belongs to an Account and supports status tracking (active, retired).
 *
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Base extends Model
{
    /** @use HasFactory<\Database\Factories\BaseFactory> */
    use HasFactory;
}
