<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials.
     */
    protected function initials(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->name) {
                    return '';
                }

                $names = explode(' ', trim($this->name));

                if (count($names) === 0) {
                    return '';
                }

                if (count($names) === 1) {
                    return strtoupper(substr($names[0], 0, 1));
                }

                return strtoupper(
                    substr($names[0], 0, 1).substr($names[count($names) - 1], 0, 1)
                );
            }
        );
    }
}
