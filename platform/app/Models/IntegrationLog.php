<?php

namespace App\Models;

use App\Enums\IntegrationLogStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Represents a log entry for integration sync operations.
 *
 * IntegrationLogs track synchronization status and messages for operations
 * performed by Integrations. They use polymorphic relationships to log syncs
 * for various models (Orders, Inventory, etc.) and store status (success,
 * error, warning), messages, and optional metadata.
 *
 * @property int $id
 * @property int $integration_id
 * @property string $loggable_type
 * @property int $loggable_id
 * @property \App\Enums\IntegrationLogStatus $status
 * @property string $message
 * @property array|null $metadata
 * @property \Illuminate\Support\Carbon|null $synced_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class IntegrationLog extends Model
{
    /** @use HasFactory<\Database\Factories\IntegrationLogFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'integration_id',
        'loggable_type',
        'loggable_id',
        'status',
        'message',
        'metadata',
        'synced_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => IntegrationLogStatus::class,
            'metadata' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Get the integration that owns this log.
     */
    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    /**
     * Get the parent loggable model.
     */
    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }
}
