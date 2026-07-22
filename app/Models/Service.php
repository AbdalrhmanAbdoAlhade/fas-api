<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name', 'image', 'status', 'room_number', 'national_id'];
    
       public function requests()
    {
        return $this->hasMany(ServiceRequest::class, 'service_id');
    }
}