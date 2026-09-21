<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Blocking extends Model
{
    protected $fillable = [
        'blockable_type',
        'blockable_id',
        'type',
        'reason',
        'blocked_at',
        'blocked_until',
        'blocked_by',
        'unblocked_at',
        'unblocked_by',
        'is_active',
    ];

    protected $casts = [
        'blocked_at'    => 'datetime',
        'blocked_until' => 'datetime',
        'unblocked_at'  => 'datetime',
        'is_active'     => 'boolean',
    ];

    // العنصر المحظور (Hotel / Property / Company / Offer / Room)
    public function blockable(): MorphTo
    {
        return $this->morphTo();
    }

    // الأدمن اللي حظر
    public function blockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }

    // الأدمن اللي فك الحظر
    public function unblockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unblocked_by');
    }

    /**
     * هل الحظر فعّال دلوقتي؟
     */
    public function getIsCurrentlyActiveAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->type === 'permanent') {
            return true;
        }

        return $this->blocked_until && $this->blocked_until->isFuture();
    }

    /**
     * Scope: الحظر الفعّال حاليًا فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('type', 'permanent')
                  ->orWhere(function ($q2) {
                      $q2->where('type', 'temporary')
                         ->where('blocked_until', '>', now());
                  });
            });
    }
}