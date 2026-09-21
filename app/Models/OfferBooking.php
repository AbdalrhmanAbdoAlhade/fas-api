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
        'selected_hotel_id',         // ✅ الفندق المختار من العميل
        'hotel_price_snapshot',      // ✅ لقطة سعر الفندق وقت الحجز
        'user_id',
        'total_price',
        'name',
        'date_of_birth',
        'national_id',
        'email',
        'phone',
        'room_password',
        'main_password',
        'status',
        'required_documents',
        'selected_options',
        'qr_code_url',
    ];

    protected $casts = [
        'required_documents' => 'array',
        'selected_options'   => 'array',
    ];

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    // ✅ العلاقة الجديدة: الفندق الذي اختاره العميل فعلياً
    public function selectedHotel()
    {
        return $this->belongsTo(Hotel::class, 'selected_hotel_id');
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
