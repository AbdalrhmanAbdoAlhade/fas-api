<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingLink extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'custom_keyword',
        'added_date',
        'visits',
        'earns',    //Corrdnator earns values from the Link !
        'purchases_count',
        'url',
        'is_archived',
        'coordinator_id',
        
    ];

    public function payments()
    {
        return $this->hasMany(Payment::class, 'tracking_link_id');
    }
    public function coordinator()
    {
        return $this->belongsTo(Coordinator::class);
    }
}
