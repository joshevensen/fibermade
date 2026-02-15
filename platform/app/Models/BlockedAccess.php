<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $type
 * @property string $value
 * @property string|null $reason
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class BlockedAccess extends Model
{
    protected $fillable = [
        'type',
        'value',
        'reason',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Scope a query to only include active (non-expired) blocks.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<BlockedAccess>  $query
     * @return \Illuminate\Database\Eloquent\Builder<BlockedAccess>
     */
    public function scopeActive($query)
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }
}
