<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'name',
        'description',
        'company_id',
        'hotel_id',
        'features',
        'people_count',
        'transportation',
        'program',
        'path',
        'cover_images',
        'images',
        'required_documents',
        'departure_time',
        'return_time',
        'price',
         'options',
    ];

    protected $casts = [
        'cover_images' => 'array',
        'images' => 'array',
           'options' => 'array',
    ];

    /**
     * علاقة العرض مع الفندق (اختيارية)
     */
    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    /**
     * علاقة العرض مع الشركة (أساسية)
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

   
    public function bookings()
    {
        return $this->hasMany(OfferBooking::class);
    }
}
