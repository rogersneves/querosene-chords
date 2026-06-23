<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MfaTrustedDevice extends Model
{
    protected $fillable = ['user_id', 'token_hash', 'expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Check whether a raw cookie token belongs to an active trusted device for this user. */
    public static function isValid(int $userId, string $rawToken): bool
    {
        return static::where('user_id', $userId)
            ->where('token_hash', hash('sha256', $rawToken))
            ->where('expires_at', '>', now())
            ->exists();
    }

    /** Create a new trusted device record and return the raw token to store in the cookie. */
    public static function issue(int $userId): string
    {
        $rawToken = Str::random(64);

        static::create([
            'user_id'    => $userId,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addDays(30),
        ]);

        return $rawToken;
    }

    /** Remove expired rows (run periodically). */
    public static function pruneExpired(): int
    {
        return static::where('expires_at', '<', now())->delete();
    }
}
