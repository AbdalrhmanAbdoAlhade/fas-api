<?php

namespace App\Traits;

use App\Models\Blocking;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait Blockable
{
    /**
     * كل سجلات الحظر لهذا العنصر (نشطة + منتهية)
     */
    public function blockings(): MorphMany
    {
        return $this->morphMany(Blocking::class, 'blockable')->latest();
    }

    /**
     * الحظر الفعّال الحالي (لو موجود)
     */
    public function activeBlocking(): MorphOne
    {
        return $this->morphOne(Blocking::class, 'blockable')
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('type', 'permanent')
                  ->orWhere(function ($q2) {
                      $q2->where('type', 'temporary')
                         ->where('blocked_until', '>', now());
                  });
            })
            ->latest();
    }

    /**
     * هل العنصر محظور دلوقتي؟
     */
    public function isBlocked(): bool
    {
        return $this->activeBlocking()->exists();
    }

    /**
     * حظر العنصر
     */
    public function block(string $type, ?string $reason, ?Carbon $until, int $byUserId): Blocking
    {
        // لو فيه حظر فعّال بالفعل، نفكّه الأول
        $this->activeBlocking()->update([
            'is_active'    => false,
            'unblocked_at' => now(),
            'unblocked_by' => $byUserId,
        ]);

        return $this->blockings()->create([
            'type'          => $type,
            'reason'        => $reason,
            'blocked_at'    => now(),
            'blocked_until' => $type === 'temporary' ? $until : null,
            'blocked_by'    => $byUserId,
            'is_active'     => true,
        ]);
    }

    /**
     * فك الحظر
     */
    public function unblock(int $byUserId): bool
    {
        $active = $this->activeBlocking()->first();

        if (!$active) {
            return false;
        }

        $active->update([
            'is_active'    => false,
            'unblocked_at' => now(),
            'unblocked_by' => $byUserId,
        ]);

        return true;
    }

    /**
     * Scope: العناصر غير المحظورة (بدون النظر للأب)
     */
    public function scopeNotBlocked($query)
    {
        return $query->whereDoesntHave('activeBlocking');
    }

    /**
     * Scope: العناصر المحظورة
     */
    public function scopeBlocked($query)
    {
        return $query->whereHas('activeBlocking');
    }

    /**
     * ✅ Scope أساسي: العناصر المرئية للمستخدم
     * - الأدمن: يشوف كل حاجة
     * - غيره: بس اللي مش محظور
     */
   public function scopeVisibleTo($query, $user = null)
{
    // الأدمن يشوف كل حاجة
    if ($user && $user->role === 'admin') {
        return $query;
    }

    return $query->where(function ($q) use ($user) {
        // approved للجميع
        $q->where('status', 'approved');

        // أو اللي هو صاحبه
        if ($user) {
            $q->orWhere('user_id', $user->id);
        }
    })->whereDoesntHave('activeBlocking');
}
}