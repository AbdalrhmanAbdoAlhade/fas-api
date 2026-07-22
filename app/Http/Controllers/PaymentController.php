<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\MerchantPaymentSetting;
use App\Models\RoomBooking;
use App\Models\PropertyBooking;
use App\Models\OfferBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\EdfaPayService;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PaymentController extends Controller
{
    /**
     * Webhook: يستقبل التحديثات من البوابة في الخلفية
     */
public function paymentCallback(Request $request)
{
    $payment = Payment::find($request->query('payment_id'));
    if (!$payment) {
        return response()->json(['message' => __('responses.payment_not_found')], 404);
    }

    if ($payment->status === 'pending') {
        $statusData = (new EdfaPayService())->checkStatus($payment->order_id, $payment->merchant_id);

        // ✅ الـ status جوه responseBody
$edfaStatus = strtoupper($statusData['responseBody']['status'] ?? '');

        $statusMap = [
            'SETTLED'              => 'completed',
            'CAPTURED'             => 'completed',
            'APPROVED'             => 'completed',
            'DECLINED'             => 'failed',
            'AUTHENTICATION_FAILURE' => 'failed',
            'REFUND'               => 'refunded',
        ];

        if ($edfaStatus && isset($statusMap[$edfaStatus])) {
            $newStatus = $statusMap[$edfaStatus];
            $payment->update(['status' => $newStatus]);

            if ($newStatus === 'completed') {
                $this->confirmBooking($payment);
            }
        }

        Log::info('EdfaPay status mapped', [
            'edfa_status' => $edfaStatus,
            'new_status'  => $payment->fresh()->status,
        ]);
    }

    return response()->json([
        'status'     => $payment->fresh()->status,
        'booking_id' => $payment->booking_id,
        'message'    => match($payment->fresh()->status) {
            'completed' => __('responses.payment_successful'),
            'failed'    => __('responses.payment_failed_reason', ['reason' => $statusData['responseBody']['reason'] ?? '']),
            'refunded'  => __('responses.payment_refunded'),
            default     => __('responses.payment_processing'),
        },
    ]);
}


public function handleWebhook(Request $request): \Illuminate\Http\JsonResponse
{
    $data = $request->all();
    Log::info('EdfaPay Webhook Payload:', $data);

    // ✅ تحقق أن الحقول الأساسية موجودة
    if (empty($data['order_id'])) {
        return response()->json(['message' => __('responses.missing_order_id')], 400);
    }

    $payment = Payment::where('order_id', $data['order_id'])->first();
    if (!$payment) {
        return response()->json(['message' => __('responses.payment_record_not_found')], 404);
    }

    $merchantSettings = MerchantPaymentSetting::where('user_id', $payment->merchant_id)->first();
    if (!$merchantSettings) {
        return response()->json(['message' => __('responses.merchant_settings_not_found')], 500);
    }

    if (!$this->isValidHash($data, $merchantSettings->password)) {
        Log::error('EdfaPay Webhook Hash Mismatch', ['order_id' => $data['order_id']]);
        return response()->json(['message' => __('responses.invalid_hash')], 403);
    }

    switch ($data['status']) {
        case 'SETTLED':
            // ✅ تجنب المعالجة المزدوجة
            if ($payment->status !== 'completed') {
                $payment->update(['status' => 'completed']);
                $this->confirmBooking($payment); // ✅ تحديث الحجز فعلياً
            }
            break;
        case 'DECLINED':
            $payment->update(['status' => 'failed']);
            break;
        case 'REFUND':
            $payment->update(['status' => 'refunded']);
            break;
    }

    return response()->json(['message' => __('responses.webhook_processed')], 200);
}

// ✅ دالة مركزية لتأكيد الحجز — تعمل مع كل أنواع الحجوزات
private function confirmBooking(Payment $payment): void
{
    try {
        $bookingClass = $payment->booking_type;

        if (!class_exists($bookingClass)) {
            Log::error('Unknown booking type: ' . $bookingClass);
            return;
        }

        $booking = $bookingClass::find($payment->booking_id);

        if (!$booking) {
            Log::error('Booking not found', [
                'type' => $bookingClass,
                'id'   => $payment->booking_id,
            ]);
            return;
        }

        // ✅ تحديث حالة الحجز
        $booking->update(['status' => 'confirmed']);

        // ✅ توليد QR Code حسب نوع الحجز
        $qrUrl = $this->generateDynamicQR($booking);

        // ✅ حفظ رابط الـ QR في الحجز
        $booking->update(['qr_code_url' => $qrUrl]);

        Log::info('Booking confirmed with QR', [
            'type'       => class_basename($bookingClass),
            'booking_id' => $booking->id,
            'qr_url'     => $qrUrl,
        ]);

    } catch (\Exception $e) {
        Log::error('confirmBooking failed: ' . $e->getMessage());
    }
}

private function generateDynamicQR($booking): string
{
    $qrContent = "";

    if ($booking instanceof \App\Models\RoomBooking) {
        $booking->load('room.hotel');
        $hotel = $booking->room->hotel ?? null;
        $mapUrl = $hotel ? "https://www.google.com/maps?q={$hotel->latitude},{$hotel->longitude}" : '';

        $qrContent = "🏨 اسم الفندق: " . ($hotel->name ?? 'N/A') . "\n"
            . ($mapUrl ? "🗺️ رابط الخريطة: {$mapUrl}\n" : "")
            . "👤 الاسم: {$booking->name}\n"
            . "📞 الهاتف: {$booking->phone}\n"
            . "🛏️ رقم الغرفة: {$booking->room_number}\n"
            . "📶 رقم الدور: {$booking->floor_number}\n"
            . "👨‍👩 البالغون: {$booking->adults}\n"
            . "👦‍👧 الأطفال: {$booking->children}\n"
            . "🕒 تسجيل الوصول: {$booking->start_date}\n"
            . "🚪 تسجيل المغادرة: {$booking->end_date}\n"
            . "🔐 باسورد الغرفة: {$booking->room_password}\n"
            . "🔐 الباسورد الرئيسي: {$booking->main_password}";

    } elseif ($booking instanceof \App\Models\PropertyBooking) {
        $booking->load('property');
        $property = $booking->property;

        $qrContent = "🏠 اسم العقار: " . ($property->title ?? 'N/A') . "\n"
            . "📍 العنوان: " . ($property->address ?? '') . "\n"
            . "👤 الاسم: {$booking->name}\n"
            . "📞 الهاتف: {$booking->phone}\n"
            . "🪪 الهوية: {$booking->national_id}\n"
            . "📆 من: {$booking->start_date} إلى {$booking->end_date}\n"
            . "👥 عدد الضيوف: {$booking->guests}\n"
            . "🔐 رمز الدخول: {$booking->access_password}\n"
            . "🔐 الباسورد الرئيسي: {$booking->main_password}";

    } elseif ($booking instanceof \App\Models\OfferBooking) {
        $booking->load('offer');
        $offer = $booking->offer;

        $qrContent = "🎟️ العرض: " . ($offer->name ?? 'N/A') . "\n"
            . "👤 الاسم: {$booking->name}\n"
            . "📞 الهاتف: {$booking->phone}\n"
            . "💰 السعر الأساسي: " . ($offer->price ?? '') . "\n";

        $selectedOptions = $booking->selected_options ?? [];
        if (!empty($selectedOptions)) {
            $qrContent .= "----------------------\n🧾 الإضافات:\n";
            foreach ($selectedOptions as $option) {
                $qrContent .= "• {$option['name']} - {$option['price']} ريال\n";
            }
            $qrContent .= "----------------------\n";
        }

        $qrContent .= "💰 السعر الكلي: {$booking->total_price}\n"
            . "🔐 باسورد الغرفة: {$booking->room_password}\n"
            . "🔐 الباسورد الرئيسي: {$booking->main_password}";
    }

    $fileName  = 'qr_' . strtolower(class_basename($booking)) . '_' . $booking->id . '.png';
    $directory = storage_path('app/public/qrcodes');

    if (!file_exists($directory)) {
        mkdir($directory, 0775, true);
    }

    \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
        ->size(300)
        ->encoding('UTF-8')
        ->generate($qrContent, $directory . '/' . $fileName);

    return url('storage/qrcodes/' . $fileName);
}

private function isValidHash($data, $password): bool
{
    // ✅ المعادلة الصحيحة لـ EdfaPay Webhook
    $email     = $data['payer_email'] ?? '';
    $transId   = $data['trans_id'] ?? '';
    $maskedPan = $data['masked_pan'] ?? '';

    $hashString     = strrev($email) . $password . $transId . strrev($maskedPan);
    $calculatedHash = md5(strtoupper($hashString));

    Log::info('Hash check', [
        'received'   => $data['hash'] ?? 'missing',
        'calculated' => $calculatedHash,
    ]);

    return isset($data['hash']) && hash_equals($calculatedHash, $data['hash']);
}

}