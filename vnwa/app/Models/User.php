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
        'coin',
        'notify_email_new_chapter',
        'notify_email_comment_reply',
        'notify_email_recommendations',
        'notify_push_new_chapter',
        'notify_push_comment_reply',
        'privacy_public_profile',
        'privacy_show_reading_history',
        'privacy_show_favorites',
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
            'coin' => 'integer',
            'notify_email_new_chapter' => 'boolean',
            'notify_email_comment_reply' => 'boolean',
            'notify_email_recommendations' => 'boolean',
            'notify_push_new_chapter' => 'boolean',
            'notify_push_comment_reply' => 'boolean',
            'privacy_public_profile' => 'boolean',
            'privacy_show_reading_history' => 'boolean',
            'privacy_show_favorites' => 'boolean',
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
     * Get user coin transactions
     */
    public function coinTransactions()
    {
        return $this->hasMany(CoinTransaction::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get user chapter unlocks
     */
    public function chapterUnlocks()
    {
        return $this->hasMany(ChapterUnlock::class);
    }

    /**
     * Check if user has unlocked a chapter
     */
    public function hasUnlockedChapter(int $chapterId): bool
    {
        return $this->chapterUnlocks()->where('manga_chapter_id', $chapterId)->exists();
    }

    /**
     * Add coin to user
     */
    public function addCoin(int $amount, ?string $description = null, ?string $referenceType = null, ?int $referenceId = null): CoinTransaction
    {
        $this->increment('coin', $amount);
        $this->refresh();

        return CoinTransaction::create([
            'user_id' => $this->id,
            'type' => 'deposit',
            'amount' => $amount,
            'description' => $description ?? 'Nạp coin',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'balance_after' => $this->coin,
        ]);
    }

    /**
     * Spend coin from user
     */
    public function spendCoin(int $amount, ?string $description = null, ?string $referenceType = null, ?int $referenceId = null): CoinTransaction
    {
        if ($this->coin < $amount) {
            throw new \Exception('Không đủ coin');
        }

        $this->decrement('coin', $amount);
        $this->refresh();

        return CoinTransaction::create([
            'user_id' => $this->id,
            'type' => 'spend',
            'amount' => $amount,
            'description' => $description ?? 'Tiêu coin',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'balance_after' => $this->coin,
        ]);
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
