<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class MerchantPaymentSetting extends Model
{
    protected $fillable = [
        'user_id',
        'merchant_key',
        'password',
        'return_url'
    ];

    protected $hidden = ['password'];
public function getRouteKeyName()
{
    // بهذا السطر، أي مسار يحتوي على {merchant_payment_setting} 
    // سيقوم لارافيل بالبحث عنه في عمود user_id وليس id
    return 'user_id';
}
    /**
     * تشفير كلمة السر تلقائياً عند الحفظ
     */
    // public function setPasswordAttribute($value): void
    // {
    //     if (!empty($value)) {
    //         $this->attributes['password'] = Crypt::encryptString($value);
    //     }
    // }

    // /**
    //  * فك التشفير عند الاستخدام
    //  * (لا نعمل accessor تلقائي عشان ما تتفكش كل مرة)
    //  */
    // public function getDecryptedPassword(): string
    // {
    //     return Crypt::decryptString($this->password);
    // }

    /**
     * علاقة تربط الإعدادات باليوزر (التاجر)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
