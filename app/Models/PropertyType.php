<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyType extends Model
{
    protected $fillable = [
        'name',
        'image',
    ];

    // علاقة مع الفنادق
    public function hotels()
    {
        return $this->hasMany(Hotel::class);
    }
    
}
