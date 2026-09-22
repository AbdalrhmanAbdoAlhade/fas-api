<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\Blockable;

class Hotel extends Model
{
    use HasTranslations, Blockable;

    public array $translatable = ['name', 'description', 'city', 'address', 'facilities'];

    protected $fillable = [
        'name',
        'images',
        'stars',
        'address',
        'country',
        'details',
        'description',
        'property_type_id',
        'property_type',
        'city',
        'area',
        'rooms_count',
        'facilities',
        'cover_image',
        'latitude',
        'longitude',
        'price_per_night',
        'pay_on_arrival_enabled',
        'user_id',
        'national_id',
        'suites_count', 
        'status',   
        'phone',
        'ownership_deed',
        'commercial_register',
        'tax_certificate',
        'distance_to_haram',
    ];

    protected $casts = [
        'images'                 => 'array',
        'details'                => 'array',
        'facilities'             => 'array',
        'cover_image'            => 'array',
        'pay_on_arrival_enabled' => 'boolean',
        'suites_count'           => 'integer', 
       'rooms_count'            => 'integer', 
    ];

    /**
     * ✅ ترجمة القيم للنصوص حسب اللغة الحالية
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

    // علاقة المستخدم (صاحب الفندق)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // علاقة الحجوزات
    public function bookings()
    {
        return $this->hasMany(RoomBooking::class);
    }
  

    // علاقة الغرف
    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function propertyType()
    {
        return $this->belongsTo(PropertyType::class);
    }

    // علاقة التقييمات
    public function reviews()
    {
        return $this->hasMany(HotelReview::class);
    }

    // العروض اللي الفندق ده هو الأساسي ليها
    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    // العروض المرتبطة عن طريق جدول hotel_offer
    public function pivotOffers()
    {
        return $this->belongsToMany(Offer::class, 'hotel_offer')
                    ->withPivot('price')
                    ->withTimestamps();
    }
}