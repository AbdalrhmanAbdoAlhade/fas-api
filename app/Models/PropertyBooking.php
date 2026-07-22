<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'user_id',
        'start_date',
        'end_date',
        'guests',
        'total_price',
        'status',
        'qr_code_url',
        'name',
        'email',
        'phone',
        'notes',
    ];

    // العلاقة مع العقار
    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    // العلاقة مع المستخدم
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function payments()
{
    return $this->morphMany(Payment::class, 'booking');
}

}
