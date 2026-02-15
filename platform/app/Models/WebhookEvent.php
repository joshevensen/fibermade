<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $stripe_id
 * @property string $type
 * @property array<string, mixed> $payload
 * @property \Illuminate\Support\Carbon|null $processed_at
 */
class WebhookEvent extends Model
{
    protected $fillable = [
        'stripe_id',
        'type',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
