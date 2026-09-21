<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\Blockable;

class Offer extends Model
{
    use HasFactory, HasTranslations, Blockable;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'hotel_id',
        'company_id',
        'name',
        'description',
        'features',
        'people_count',
        'transportation',
        'program',
        'program_includes',
        'path',
        'required_documents',
        'departure_time',
        'return_time',
        'price',
        'cover_images',
        'images',
        'options',
        'discount_type',
        'discount_value',
        'discount_starts_at',
        'discount_ends_at',
        'discount_enabled',
    ];

    protected $casts = [
        'cover_images'       => 'array',
        'images'             => 'array',
        'options'            => 'array',
        'program_includes'   => 'array',
        'required_documents' => 'array',
        'departure_time'     => 'datetime',
        'return_time'        => 'datetime',
        'discount_value'     => 'decimal:2',
        'discount_starts_at' => 'datetime',
        'discount_ends_at'   => 'datetime',
        'discount_enabled'   => 'boolean',
    ];

    protected $appends = ['is_discount_active', 'final_price'];

public function toArray(): array
{
    $attributes = parent::toArray();

    // هل فيه Accept-Language header؟
    $hasLocaleHeader = request()->hasHeader('Accept-Language');

    foreach ($this->getTranslatableAttributes() as $field) {
        if ($hasLocaleHeader) {
            // ✅ فيه header → رجّع الترجمة الحالية فقط (string)
            $attributes[$field] = $this->getTranslation($field, app()->getLocale());
        } else {
            // ✅ مفيش header → رجّع كل الترجمات (object)
            $attributes[$field] = $this->getTranslations($field);
        }
    }

    return $attributes;
}

    public function getIsDiscountActiveAttribute(): bool
    {
        if (!$this->discount_enabled) return false;
        if (!$this->discount_type || !$this->discount_value) return false;

        $now = now();
        if ($this->discount_starts_at && $now->lt($this->discount_starts_at)) return false;
        if ($this->discount_ends_at && $now->gt($this->discount_ends_at)) return false;

        return true;
    }

    public function getFinalPriceAttribute(): float
    {
        if (!$this->is_discount_active) {
            return (float) $this->price;
        }

        if ($this->discount_type === 'percentage') {
            $discountAmount = ($this->price * $this->discount_value) / 100;
        } else {
            $discountAmount = $this->discount_value;
        }

        return max(0, (float) $this->price - $discountAmount);
    }

    // العلاقة مع الفندق الأساسي
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    // العلاقة مع الشركة
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // الفنادق المتعددة المرتبطة بالعرض
    public function hotels()
    {
        return $this->belongsToMany(Hotel::class, 'hotel_offer')
                    ->withPivot('price')
                    ->withTimestamps();
    }

    public function bookings()
    {
        return $this->hasMany(OfferBooking::class);
    }

    /**
     * ✅ visibleTo مخصص: يخفي العرض لو:
     *    - العرض نفسه محظور
     *    - أو الفندق الأساسي محظور
     *    - أو الشركة محظورة
     */
    public function scopeVisibleTo($query, $user = null)
    {
        if ($user && $user->role === 'admin') {
            return $query;
        }

        return $query
            ->whereDoesntHave('activeBlocking')
            ->where(function ($q) use ($user) {
                $q->whereNull('hotel_id')
                  ->orWhereHas('hotel', function ($q2) use ($user) {
                      $q2->visibleTo($user);
                  });
            })
            ->where(function ($q) use ($user) {
                $q->whereNull('company_id')
                  ->orWhereHas('company', function ($q2) use ($user) {
                      $q2->visibleTo($user);
                  });
            });
    }
}