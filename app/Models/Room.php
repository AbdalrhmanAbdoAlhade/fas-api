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
        'type',
        'cover_image',
        'images',
        'details',
        'size',
        'facilities',
        'description',
        'floor_number',
        'room_number',
        'price_per_night',
        'quantity',
        'max_occupancy',          // ← كان ناقص
    ];

    protected $casts = [
        'images'        => 'array',
        'quantity'      => 'integer',
        'max_occupancy' => 'integer',  // ← كان ناقص
    ];

    /**
     * ✅ ترجمة الحقول المترجمة للنصوص حسب اللغة الحالية
     */
    public function toArray(): array
    {
        $attributes = parent::toArray();

        $wantsAll = strtolower((string) request()->header('Accept-Language')) === 'all';

        foreach ($this->getTranslatableAttributes() as $field) {
            if ($wantsAll) {
                $attributes[$field] = $this->getTranslations($field);
            } else {
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
     * ✅ visibleTo مخصص
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