<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents a dye used in colorway creation.
 *
 * @property int $id
 * @property int $account_id
 * @property string $name
 * @property string|null $manufacturer
 * @property string|null $notes
 * @property bool $does_bleed
 * @property bool $do_like
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Dye extends Model
{
    /** @use HasFactory<\Database\Factories\DyeFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'account_id',
        'name',
        'manufacturer',
        'notes',
        'does_bleed',
        'do_like',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'does_bleed' => 'boolean',
            'do_like' => 'boolean',
        ];
    }

    /**
     * Get the account that owns this dye.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the colorways that use this dye.
     */
    public function colorways(): BelongsToMany
    {
        return $this->belongsToMany(Colorway::class, 'colorway_dye')
            ->withPivot('dry_weight', 'concentration', 'wet_amount', 'notes')
            ->withTimestamps();
    }
}
