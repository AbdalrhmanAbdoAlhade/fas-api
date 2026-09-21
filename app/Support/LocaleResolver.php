<?php

namespace App\Support;

class LocaleResolver
{
    public const SUPPORTED_LOCALES = ['ar', 'en', 'ur', 'tr', 'id'];

    /**
     * خريطة أكواد الدول (مفتاح الاتصال الدولي) إلى اللغة المناسبة.
     */
    protected static array $phoneLocaleMap = [
        // تركي
        '90'  => 'tr',
        // أردو
        '92'  => 'ur',
        // إندونيسي
        '62'  => 'id',
        // عربي - دول الخليج والعالم العربي
        '966' => 'ar', // السعودية
        '971' => 'ar', // الإمارات
        '965' => 'ar', // الكويت
        '974' => 'ar', // قطر
        '973' => 'ar', // البحرين
        '968' => 'ar', // عمان
        '962' => 'ar', // الأردن
        '964' => 'ar', // العراق
        '961' => 'ar', // لبنان
        '963' => 'ar', // سوريا
        '967' => 'ar', // اليمن
        '970' => 'ar', // فلسطين
        '218' => 'ar', // ليبيا
        '216' => 'ar', // تونس
        '213' => 'ar', // الجزائر
        '212' => 'ar', // المغرب
        '249' => 'ar', // السودان
        '20'  => 'ar', // مصر
    ];

    public static function fromHeader(?string $header): ?string
    {
        if (!$header) {
            return null;
        }

        $firstLang = trim(explode(',', $header)[0]);
        $firstLang = trim(explode(';', $firstLang)[0]);
        $short = strtolower(substr($firstLang, 0, 2));

        return in_array($short, self::SUPPORTED_LOCALES, true) ? $short : null;
    }

    /**
     * استنتاج اللغة من رقم الهاتف بناءً على كود الدولة الدولي.
     * بيدعم صيغ: +966..., 00966..., أو رقم محلي مصري زي 010xxxxxxxx.
     */
    public static function fromPhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        $normalized = preg_replace('/[^\d+]/', '', $phone);
        $normalized = preg_replace('/^00/', '', $normalized);
        $normalized = ltrim($normalized, '+');

        if ($normalized === '') {
            return null;
        }

        // رقم محلي بدون كود دولة (بيبدأ بصفر وطوله 11 رقم أو أقل) → نفترض عربي
        if (strlen($normalized) <= 11 && str_starts_with($normalized, '0')) {
            return 'ar';
        }

        // نجرب كود الدولة بطول 3 أرقام الأول، بعدين طول رقمين
        foreach ([3, 2] as $len) {
            $prefix = substr($normalized, 0, $len);
            if (isset(self::$phoneLocaleMap[$prefix])) {
                return self::$phoneLocaleMap[$prefix];
            }
        }

        return null;
    }
        /**
     * تحديد اللغة وقت التسجيل بالأولوية:
     * إدخال صريح من المستخدم → استنتاج من رقم الهاتف → افتراضي
     */
    public static function resolveForRegistration(?string $explicitLocale, ?string $phone): string
    {
        if ($explicitLocale && in_array($explicitLocale, self::SUPPORTED_LOCALES, true)) {
            return $explicitLocale;
        }

        return self::fromPhone($phone) ?? config('app.fallback_locale', 'ar');
    }

    /**
     * تحديد اللغة النهائية بالأولوية:
     * هيدر Accept-Language → لغة محفوظة عند المستخدم → استنتاج من التليفون → افتراضي
     */
    public static function resolve(?string $header, ?string $storedUserLocale = null, ?string $phone = null): string
    {
        return self::fromHeader($header)
            ?? (in_array($storedUserLocale, self::SUPPORTED_LOCALES, true) ? $storedUserLocale : null)
            ?? self::fromPhone($phone)
            ?? config('app.fallback_locale', 'ar');
    }
}
