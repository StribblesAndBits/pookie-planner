<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    public const COLOR_PREFERENCES = ['#D6486B', '#6B3F38', '#5C6E4A', '#D9A441'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'color_preference',
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

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public static function availableColorPreferences(?int $ignoringUserId = null): array
    {
        $query = static::query()->whereNotNull('color_preference');

        if ($ignoringUserId !== null) {
            $query->where('id', '!=', $ignoringUserId);
        }

        $takenColors = $query->pluck('color_preference')->all();

        return array_values(array_diff(self::COLOR_PREFERENCES, $takenColors));
    }

    public static function nextAvailableColorPreference(?int $ignoringUserId = null): ?string
    {
        return self::availableColorPreferences($ignoringUserId)[0] ?? null;
    }
}
