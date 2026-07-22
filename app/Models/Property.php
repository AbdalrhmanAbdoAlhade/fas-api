<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'city',
        'address',
        'rooms',
        'beds',
        'bathrooms',
        'guests',
        'price_per_night',
        'is_available',
        'images',
        'main_image',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'images' => 'array',
        'is_available' => 'boolean',
        'price_per_night' => 'decimal:2',
    ];

    // علاقة كل عقار بمستخدم (صاحب العقار)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function reviews()
{
    return $this->hasMany(HotelReview::class, 'properties_id');
}

}
