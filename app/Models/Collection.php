<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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
 * @property string|null $description
 * @property \App\Enums\BaseStatus $status
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Collection extends Model
{
    /** @use HasFactory<\Database\Factories\CollectionFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'description',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => \App\Enums\BaseStatus::class,
        ];
    }

    /**
     * Get the account that owns this collection.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the colorways that belong to this collection.
     */
    public function colorways(): BelongsToMany
    {
        return $this->belongsToMany(Colorway::class, 'colorway_collection')
            ->withTimestamps();
    }
}
