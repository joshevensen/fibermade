<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a collection of colorways in the catalog.
 *
 * Collections allow dyers to group related Colorways together for organizational
 * purposes. Each Collection belongs to an Account and can contain multiple
 * Colorways through a many-to-many relationship.
 *
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Collection extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionFactory> */
    use HasFactory;
}
