<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hotel extends Model
{
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
        'rooms',
        'facilities',

        'cover_image',
        'latitude',
        'longitude',
        'price_per_night',
        'user_id',
    ];

    protected $casts = [
        'images' => 'array',
        'details' => 'array',
        'facilities' => 'array',
        'cover_image' => 'array',
    ];

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

public function offers()
{
    return $this->hasMany(Offer::class);
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
}
