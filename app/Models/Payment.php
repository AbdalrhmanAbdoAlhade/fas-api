<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'merchant_id', // 👈 ضيفنا ده عشان نربط الدفعة بالتاجر صاحب الخدمة
        'booking_id',
        'booking_type',
        'order_id',
        'order_name',
        'amount',
        'currency',
        'payment_method',
        'status',
        'redirect_url',
        'transaction_id',
        'order_description',
        'tracking_link_id',
        'qr_code_url',
    ];

    /**
     * علاقة المالك/التاجر (صاحب الفندق أو العقار)
     */
    public function merchant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    /**
     * علاقة مباشرة بتجيب "إعدادات الدفع" الخاصة بالتاجر ده
     */
    public function merchantSettings(): BelongsTo
    {
        // هنفترض إن العلاقة في جدول الـ settings مربوطة بـ user_id التاجر
        return $this->belongsTo(MerchantPaymentSetting::class, 'merchant_id', 'user_id');
    }

    public function trackingLink(): BelongsTo
    {
        return $this->belongsTo(TrackingLink::class, 'tracking_link_id');
    }

  public function booking()
{
    return $this->morphTo('booking', 'booking_type', 'booking_id');
}

}