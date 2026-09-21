<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\Blockable;

class Property extends Model
{
    use HasFactory, HasTranslations, Blockable;

    public array $translatable = ['title', 'description', 'type', 'city', 'address'];

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'city',
        'address',
        'country',              // ← جديد (مش مترجم)
        'area',
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
        'national_id',
        'phone',
        'ownership_deed',
        'commercial_register',
        'status',  
        'tax_certificate',
    ];

    protected $casts = [
        'images'          => 'array',
        'is_available'    => 'boolean',
        'price_per_night' => 'decimal:2',
    ];

    public function toArray(): array
    {
        $attributes = parent::toArray();

        $wantsAll = strtolower((string) request()->header('Accept-Language')) === 'all';

        foreach ($this->getTranslatableAttributes() as $field) {
            if ($wantsAll) {
                $attributes[$field] = $this->getTranslations($field);
            } else {
                $attributes[$field] = $this->getTranslation($field, app()->getLocale());
            }
        }

        return $attributes;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasMany(HotelReview::class, 'properties_id');
    }
}