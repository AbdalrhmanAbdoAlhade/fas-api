<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'description',
        'logo',
        'website',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * العلاقة مع المستخدم
     * كل شركة تتبع مستخدم واحد
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * علاقة مستقبلية لو حبيت تربط الشركة بالعروض (offers)
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
