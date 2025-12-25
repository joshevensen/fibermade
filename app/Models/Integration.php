<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
 * @property string $type
 * @property string $name
 * @property string $credentials
 * @property array|null $settings
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Integration extends Model
{
    /** @use HasFactory<\Database\Factories\IntegrationFactory> */
    use HasFactory;
}
