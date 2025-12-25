<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Represents an account that can be used by all account types (wholesale, retail, show).
 *
 * Accounts store buyer relationships and can represent wholesale buyers, retail
 * customers, or show organizers. Each Account belongs to a User (owner) and owns
 * catalog items (Colorways, Bases, Collections, Tags). Accounts support account-level
 * pricing rules and are used for wholesale order management and relationship tracking.
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property array|null $address
 * @property array|null $pricing_rules
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;
}
