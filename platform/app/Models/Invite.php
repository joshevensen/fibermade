<?php

namespace App\Models;

use App\Enums\InviteType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property InviteType $invite_type
 * @property string $email
 * @property string $token
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $accepted_at
 * @property string $inviter_type
 * @property int $inviter_id
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Invite extends Model
{
    protected $fillable = [
        'invite_type',
        'email',
        'token',
        'expires_at',
        'accepted_at',
        'inviter_type',
        'inviter_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'invite_type' => InviteType::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invite $invite): void {
            if (empty($invite->token)) {
                $invite->token = $invite->generateToken();
            }
            if (empty($invite->expires_at)) {
                $invite->expires_at = now()->addDays(7);
            }
        });
    }

    public function inviter(): MorphTo
    {
        return $this->morphTo();
    }

    public function generateToken(): string
    {
        return Str::random(64);
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Invite>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Invite>
     */
    public function scopePending($query)
    {
        return $query->whereNull('accepted_at')->where('expires_at', '>', now());
    }
}
