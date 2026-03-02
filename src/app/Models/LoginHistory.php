<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property ?int $user_id
 * @property string $email
 * @property string $ip_address
 * @property ?string $user_agent
 * @property string $status
 * @property \Illuminate\Support\Carbon $created_at
 */
class LoginHistory extends Model
{
    /** @use HasFactory<\Database\Factories\LoginHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record a successful login.
     */
    public static function recordSuccess(User $user, string $ip, ?string $userAgent): self
    {
        return self::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status' => 'success',
        ]);
    }

    /**
     * Record a failed login attempt.
     */
    public static function recordFailure(string $email, string $ip, ?string $userAgent): self
    {
        return self::create([
            'user_id' => User::where('email', $email)->value('id'),
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'status' => 'failed',
        ]);
    }
}
