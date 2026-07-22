<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelReview extends Model
{
       protected $fillable = [
        'hotel_id',
        'company_id',
        'properties_id',
        'user_id',
        'stars',
        'comment',
    ];


    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
       public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
      public function property()
    {
        return $this->belongsTo(Property::class, 'properties_id');
    }

}
