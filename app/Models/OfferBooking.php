<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfferBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id',
        'hotel_id',
        'user_id',
        'name',
        'date_of_birth',
        'qr_code_url',
        'national_id',
        'email',
        'phone',
        'total_price',
        'room_password',
        'main_password',
        'status',
        'required_documents',
        'selected_options',
    ];

    protected $casts = [
        'required_documents' => 'array',
          'selected_options' => 'array',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function payments()
{
    return $this->morphMany(Payment::class, 'booking');
}

}
