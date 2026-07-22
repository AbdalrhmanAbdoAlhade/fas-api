<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Coordinator extends Model
{
    use HasFactory;

    // هذا السطر هو الحل! يخبر Laravel أن 'id' ليس ترقيمًا تلقائيًا.
       public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'name', 'email', 'phone', 'id', 'password'
    ];

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    // Relationship with tracking links
    public function trackingLinks()
    {
        return $this->hasMany(TrackingLink::class);
    }

    protected $hidden = [
        'password',
    ];
    
    public function user()
    {
        // نحدد أن المفتاح الأجنبي هو 'id' لربط الـ'coordinator' بالـ'user'.
        return $this->belongsTo(User::class, 'id');
    }
}
