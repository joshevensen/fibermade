<?php

namespace App\Models;

use App\Enums\IntegrationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Represents an integration connection (Shopify, future integrations).
 *
 * Integrations store connection details and settings for external services.
 * In Stage 1, this primarily represents Shopify connections via the GraphQL
 * Admin API. Credentials are stored encrypted, and settings are stored as JSON.
 * Each Integration belongs to a User and can be activated or deactivated.
 *
 * @property int $id
 * @property int $user_id
 * @property \App\Enums\IntegrationType $type
 * @property string $credentials
 * @property array|null $settings
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Integration extends Model
{
    /** @use HasFactory<\Database\Factories\IntegrationFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'credentials',
        'settings',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => IntegrationType::class,
            'settings' => 'array',
            'active' => 'boolean',
        ];
    }

    /**
     * Get the user that owns this integration.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the integration logs for this integration.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class);
    }
}
