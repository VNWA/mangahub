<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'discord_id',
        'provider',
        'avatar',
        'is_guest',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
            'is_guest' => 'boolean',
        ];
    }

    /**
     * Get user favorites
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get user reading history
     */
    public function readingHistory()
    {
        return $this->hasMany(ReadingHistory::class)->orderBy('last_read_at', 'desc');
    }

    /**
     * Get list of social providers that the user has connected.
     */
    public function getConnectedProviders(): array
    {
        $providers = [];

        if ($this->password) {
            $providers[] = 'email';
        }

        if ($this->google_id) {
            $providers[] = 'google';
        }

        if ($this->discord_id) {
            $providers[] = 'discord';
        }

        return $providers;
    }

    /**
     * Check if user has connected a specific provider.
     */
    public function hasProvider(string $provider): bool
    {
        return match ($provider) {
            'email' => (bool) $this->password,
            'google' => (bool) $this->google_id,
            'discord' => (bool) $this->discord_id,
            default => false,
        };
    }
}
