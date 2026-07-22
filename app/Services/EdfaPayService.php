<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\MerchantPaymentSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EdfaPayService
{
    /**
     * دالة الاستعلام عن حالة الطلب من EdfaPay
     */

public function checkStatus($orderId, $merchantId)
{
    try {
        $merchantSettings = \App\Models\MerchantPaymentSetting::where('user_id', $merchantId)->first();

        if (!$merchantSettings) {
            Log::error('checkStatus: Merchant settings not found for user_id: ' . $merchantId);
            return ['status' => 'Unknown'];
        }

        $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ])
            ->withOptions(['verify' => false])
            ->post('https://api.edfapay.com/payment/status', [
                'action'           => 'TRANSACTION_STATUS',
                'merchant_id' => $merchantSettings->merchant_key,
                'password'         => $merchantSettings->password,
                'order_id'         => $orderId,
            ]);

        Log::info('EdfaPay checkStatus response:', $response->json() ?? []);

        return $response->json() ?? ['status' => 'Unknown'];

    } catch (\Exception $e) {
        Log::error('checkStatus Exception: ' . $e->getMessage());
        return ['status' => 'Unknown'];
    }
}
    public function initiatePayment(array $data)
    {
        // 1. تحديد نوع الموديل وجلب الحجز ديناميكياً
        $modelClass = $data['booking_type'];
        $booking = $modelClass::findOrFail($data['booking_id']);

        $ownerId = null;

        // 2. تحديد مالك الفندق (التاجر) حسب نوع الحجز
        if ($booking instanceof \App\Models\RoomBooking) {
            $booking->load('room.hotel');
            $ownerId = $booking->room->hotel->user_id ?? null;
        } elseif ($booking instanceof \App\Models\OfferBooking) {
            $booking->load('offer.company');
            $ownerId = $booking->offer->company->user_id ?? null;
        } elseif ($booking instanceof \App\Models\PropertyBooking) {
        // إضافة هذا الشرط للعقارات
        $booking->load('property');
        $ownerId = $booking->property->user_id ?? null;
    }

        if (!$ownerId) {
            throw new \Exception('لا يمكن تحديد التاجر (User ID) لهذا الحجز، تأكد من ارتباط الحجز بفندق.');
        }

        // 2. جلب إعدادات التاجر
        $merchantSettings = MerchantPaymentSetting::where('user_id', $ownerId)->first();
        if (!$merchantSettings) {
            throw new \Exception('هذا التاجر لم يضبط إعدادات الدفع');
        }

        $orderId = time() . mt_rand(1000, 9999);
        $currency = 'SAR';
        $description = 'Booking Payment - ' . class_basename($data['booking_type']);

        // 3. إنشاء سجل الدفع
        $payment = Payment::create([
            'user_id'           => auth('sanctum')->id(),
            'merchant_id'       => $ownerId,
            'booking_id'        => $data['booking_id'],
            'booking_type'      => $data['booking_type'],
            'order_id'          => $orderId,
            'amount'            => $data['amount'],
            'currency'          => $currency,
            'order_description' => $description,
            'status'            => 'pending',
        ]);

        // 4. حساب الهاش
        $hash = sha1(md5(strtoupper($orderId . $data['amount'] . $currency . $description . $merchantSettings->password)));

        // 5. الاتصال بـ EdfaPay
        $payload = [
            'action'            => 'SALE',
            'edfa_merchant_id'  => $merchantSettings->merchant_key,
            'payer_ip'          => $_SERVER['REMOTE_ADDR'] ?? request()->ip(),
            'order_id'          => $orderId,
            'order_amount'      => $data['amount'],
            'order_currency'    => $currency,
            'order_description' => $description,
            'req_token'         => 'N',
            'auth'              => 'N',
            'payer_first_name'  => $data['first_name'],
            'payer_last_name'   => $data['last_name'],
            'payer_email'       => $data['email'],
            'payer_phone'       => $data['phone'],
            'return_url'        => env('EDFA_PAY_RETURN_URL'),
'term_url_3ds' => 'https://api.faz-an.com/api/payment-status?payment_id=' . $payment->id,
            'hash'              => $hash,
        ];

        $response = Http::asForm()->post('https://api.edfapay.com/payment/initiate', $payload);

        if ($response->successful() && !empty($response->json('redirect_url'))) {
            $payment->update(['redirect_url' => $response->json('redirect_url')]);
            return [
                'status' => true,
                'payment' => $payment,
                'redirect_url' => $response->json('redirect_url')
            ];
        }

        throw new \Exception('فشل في إنشاء رابط الدفع: ' . $response->body());
    }
}
