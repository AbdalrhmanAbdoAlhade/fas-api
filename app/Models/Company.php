<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\Blockable;

class Company extends Model
{
    use HasFactory, HasTranslations, Blockable;

    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'description',
        'logo',
        'website',
        'is_active',
            'national_id',
            'status',  
    'phone',
    'ownership_deed',
    'commercial_register',
    'tax_certificate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
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
            $attributes[$field] = $this->getTranslations($field);
        } else {
            $attributes[$field] = $this->getTranslation($field, app()->getLocale());
        }
    }

    return $attributes;
}

    /**
     * العلاقة مع المستخدم
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة الشركة بالعروض
     */
    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * 🔹 التقييمات المرتبطة بهذه الشركة
     */
    public function reviews()
    {
        return $this->hasMany(HotelReview::class, 'company_id');
    }

    public function bookings()
    {
        return $this->hasManyThrough(OfferBooking::class, Offer::class);
    }
}