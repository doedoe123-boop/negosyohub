<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $store_id
 * @property float $amount
 * @property \Illuminate\Support\Carbon $period_start
 * @property \Illuminate\Support\Carbon $period_end
 * @property string $status
 * @property ?string $reference
 * @property ?\Illuminate\Support\Carbon $paid_at
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $updated_at
 */
class Payout extends Model
{
    /** @use HasFactory<\Database\Factories\PayoutFactory> */
    use HasFactory;

    use SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    /** @var list<string> */
    protected $fillable = [
        'store_id',
        'amount',
        'period_start',
        'period_end',
        'status',
        'reference',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'datetime',
        ];
    }

    // ── Relationships ──────────────────────────────────────────────────

    /**
     * Return the store relationship.
     */
    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    /**
     * Return the payout line items (order → earning breakdown).
     */
    public function lines(): HasMany
    {
        return $this->hasMany(PayoutLine::class);
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    /**
     * Scope payouts belonging to a specific store.
     */
    public function scopeForStore(Builder $query, int $storeId): Builder
    {
        return $query->where('store_id', $storeId);
    }

    /**
     * Scope pending payouts.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope paid payouts.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    // ── Status Helpers ─────────────────────────────────────────────────

    /**
     * Determine if the payout is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Determine if the payout has been paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Mark the payout as processing.
     */
    public function markProcessing(): self
    {
        $this->update(['status' => self::STATUS_PROCESSING]);

        return $this;
    }

    /**
     * Mark the payout as paid with a reference.
     */
    public function markPaid(string $reference): self
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'reference' => $reference,
            'paid_at' => now(),
        ]);

        return $this;
    }

    /**
     * Mark the payout as failed.
     */
    public function markFailed(): self
    {
        $this->update(['status' => self::STATUS_FAILED]);

        return $this;
    }
}
