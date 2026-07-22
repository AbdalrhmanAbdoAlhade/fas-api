<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceRequest extends Model
{
    protected $fillable = [
        'service_id',
        'room_number',
        'guest_name',
        'request_time',
        'status',
        'hotel_id',
        'user_id',
        'notes'
    ];
        public function hotel()
    {
        return $this->belongsTo(Hotel::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class); // كل فندق مرتبط بمستخدم
    }
    // علاقة الطلب مع الخدمة
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}