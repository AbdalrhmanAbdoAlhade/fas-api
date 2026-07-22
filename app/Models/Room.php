<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'hotel_id',
        'name',
        'cover_image',
        'images',
        'details',
        'size',
        'facilities',
        'description',
        'price_per_night',
        'room_number',
        'floor_number',
    ];

    protected $casts = [
        'images' => 'array',
        'cover_image' => 'array',
    ];

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function bookings()
    {
        return $this->hasMany(RoomBooking::class);
    }
}
