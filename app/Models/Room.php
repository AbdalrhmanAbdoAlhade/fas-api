<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\Blockable;

class Room extends Model
{
    use HasTranslations, Blockable;

    public array $translatable = ['name', 'description', 'details', 'facilities'];

    protected $fillable = [
        'hotel_id',
        'name',
        'cover_image',
        'images',
        'details',
        'size',
        'facilities',
        'description',
        'floor_number',
        'room_number',
        'price_per_night',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    /**
     * ✅ ترجمة الحقول المترجمة للنصوص حسب اللغة الحالية
     */
public function toArray(): array
{
    $attributes = parent::toArray();

    // هل المستخدم طلب كل الترجمات؟
    $wantsAll = strtolower((string) request()->header('Accept-Language')) === 'all';

    foreach ($this->getTranslatableAttributes() as $field) {
        if ($wantsAll) {
            // ✅ رجّع كل الترجمات
            $attributes[$field] = $this->getTranslations($field);
        } else {
            // ✅ رجّع الترجمة الحالية فقط
            $attributes[$field] = $this->getTranslation($field, app()->getLocale());
        }
    }

    return $attributes;
}
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * ✅ visibleTo مخصص: يخفي الغرفة لو:
     *    - الغرفة نفسها محظورة
     *    - أو الفندق بتاعها محظور
     */
    public function scopeVisibleTo($query, $user = null)
    {
        if ($user && $user->role === 'admin') {
            return $query;
        }

        return $query
            ->whereDoesntHave('activeBlocking')
            ->whereHas('hotel', function ($q) use ($user) {
                $q->visibleTo($user);
            });
    }
}