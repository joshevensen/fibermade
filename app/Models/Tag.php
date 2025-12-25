<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents a tag for organizing colorways and potentially other catalog items.
 *
 * Tags provide flexible categorization for Colorways, allowing dyers to apply
 * custom organizational labels. Each Tag belongs to an Account and can be
 * associated with multiple Colorways through a many-to-many relationship.
 *
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string $slug
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Tag extends Model
{
    /** @use HasFactory<\Database\Factories\TagFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'name',
        'slug',
        'is_active',
    ];
}
