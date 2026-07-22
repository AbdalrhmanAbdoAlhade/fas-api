<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;



class RoomBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'offer_id',
        'hotel_id', 
        'start_date',
        'end_date',
        'number_of_rooms',
        'number_of_guests',
        'adults',
        'children',
        'qr_code_url',
        'total_price',
        'name',
        'email',
        'phone',
        'room_number',        
        'floor_number',       
        'room_password',      
        'main_password',      
       'uuid',
       'required_documents',
       'user_id',
       'status',
       'national_id',
        'qr_code_filename',
       'title',
       'date_of_birth',
    ];
    
    protected $casts = [
    'required_documents' => 'array',
];

    
    public function room()
    {
        return $this->belongsTo(Room::class);
    }
    
    protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        $model->uuid = Str::uuid()->toString();
    });
}

public function offer()
{
    return $this->belongsTo(Offer::class);
}

   public function hotel()
    {
        return $this->belongsTo(Hotel::class);
}

public function payments()
{
    return $this->morphMany(Payment::class, 'booking');
}

}