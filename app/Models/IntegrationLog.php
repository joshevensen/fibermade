<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
 * @property string $status
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
}
