<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Property;
use App\Models\PropertyBooking;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Auth;

class PropertyBookingController extends Controller
{
    /**
     * إنشاء حجز جديد لعقار
     */
 public function book(Request $request)
{
    $validator = Validator::make($request->all(), [
        'property_id'          => 'required|exists:properties,id',
        'start_date'           => 'required|date|after_or_equal:today',
        'end_date'             => 'required|date|after:start_date',
        'guests'               => 'required|integer|min:1',
        'name'                 => 'required|string',
        'national_id'          => 'required|string',
        'email'                => 'required|email',
        'phone'                => 'required|string',
        'title'                => 'required|in:Mr,Mrs,Miss',
        'required_documents.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // تحميل الملفات
    $uploadedDocuments = [];
    if ($request->hasFile('required_documents')) {
        foreach ($request->file('required_documents') as $file) {
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/property_documents', $filename);
            $uploadedDocuments[] = url('storage/property_documents/' . $filename);
        }
    }

    $property     = Property::findOrFail($request->property_id);
    $days         = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) ?: 1;
    $totalPrice   = $days * $property->price_per_night;

    $accessPassword = strtoupper('PR' . rand(1000, 9999)) . '@';
    $mainPassword   = strtoupper('AC' . rand(100, 999)) . '!@' . rand(1, 9);

    $booking = PropertyBooking::create([
        'property_id'        => $property->id,
        'user_id'            => Auth::id(), // ✅ من التوكن مش من الريكويست
        'start_date'         => $request->start_date,
        'end_date'           => $request->end_date,
        'guests'             => $request->guests,
        'total_price'        => $totalPrice,
        'name'               => $request->name,
        'national_id'        => $request->national_id,
        'email'              => $request->email,
        'phone'              => $request->phone,
        'title'              => $request->title,
        'status'             => 'pending',
        'access_password'    => $accessPassword,
        'main_password'      => $mainPassword,
        'required_documents' => $uploadedDocuments,
    ]);

    // ✅ استدعاء بوابة الدفع
    try {
        $paymentResult = app(\App\Services\EdfaPayService::class)->initiatePayment([
            'booking_id'   => $booking->id,
            'booking_type' => get_class($booking),
            'amount'       => $totalPrice,
            'email'        => $request->email,
            'phone'        => $request->phone,
            'first_name'   => $request->name,
            'last_name'    => $request->name,
        ]);

        return response()->json([
            'status'       => true,
            'message'      => __('responses.booking_successful_payment_pending'),
            'booking_id'   => $booking->id,
            'redirect_url' => $paymentResult['redirect_url'],
            'booking'      => [
                'id'                 => $booking->id,
                'status'             => $booking->status,
                'start_date'         => $booking->start_date,
                'end_date'           => $booking->end_date,
                'total_price'        => $booking->total_price,
                'required_documents' => $uploadedDocuments,
                'property'           => [
                    'name'            => $property->title,
                    'type'            => $property->type,
                    'address'         => $property->address,
                    'price_per_night' => $property->price_per_night,
                ],
            ],
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => __('responses.booking_creation_payment_failed', ['error' => $e->getMessage()])
        ], 500);
    }
}
    /**
     * تحديث بيانات حجز
     */
    public function update(Request $request, $id)
    {
        $booking = PropertyBooking::findOrFail($id);
        $user = Auth::user();

        // تحقق من الصلاحيات
        if ($user->role !== 'admin' && !($user->role === 'property_owner' && $booking->property->user_id == $user->id)) {
            return response()->json(['message' => __('responses.unauthorized')], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'date|after_or_equal:today',
            'end_date' => 'date|after:start_date',
            'guests' => 'integer|min:1',
            'status' => 'in:pending,confirmed,paid,cancelled,completed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $booking->update($request->all());
        return response()->json(['message' => __('responses.booking_updated'), 'data' => $booking]);
    }

    /**
     * إلغاء حجز
     */
public function cancel($id)
{
    $user = auth('sanctum')->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => __('responses.unauthenticated')
        ], 401);
    }

    // التحقق من وجود الحجز وملكيته للمستخدم
    $booking = PropertyBooking::where('id', $id)
        ->where('user_id', $user->id)
        ->first();

    if (!$booking) {
        return response()->json([
            'status' => false,
            'message' => __('responses.cannot_cancel_non_owned_booking')
        ], 403);
    }

    // تحديث الحالة إلى "cancelled"
    $booking->update(['status' => 'cancelled']);

    return response()->json([
        'status' => true,
        'message' => __('responses.booking_cancelled_successfully'),
        'data' => $booking
    ]);
}

    /**
     * عرض جميع الحجوزات
     */
    public function index(Request $request)
    {
        $query = PropertyBooking::with('property')->latest();

        if ($request->filled('national_id')) {
            $query->where('national_id', $request->national_id);
        }

        $bookings = $query->get()->map(function ($booking) {
            return $this->addQrToBooking($booking);
        });

        return response()->json(['data' => $bookings]);
    }

/**
 * عرض جميع الحجوزات الخاصة بعقار معين
 */
public function getPropertyBookings($propertyId)
{
    // التحقق من وجود العقار
    $property = Property::find($propertyId);
    if (!$property) {
        return response()->json([
            'status' => false,
            'message' => __('responses.property_not_found')
        ], 404);
    }

    // جلب الحجوزات الخاصة بالعقار (المؤكدة أو المدفوعة فقط)
    $bookings = PropertyBooking::where('property_id', $propertyId)
        ->whereIn('status', ['confirmed', 'paid'])
        ->select('id', 'name', 'start_date', 'end_date', 'status', 'guests', 'total_price')
        ->orderBy('start_date', 'asc')
        ->get();

    if ($bookings->isEmpty()) {
        return response()->json([
            'status' => true,
            'message' => __('responses.no_bookings_for_property'),
            'data' => []
        ]);
    }

    // تجهيز البيانات
    $data = $bookings->map(function ($booking) {
        return [
            'booking_id'   => $booking->id,
            'name'         => $booking->name,
            'start_date'   => $booking->start_date,
            'end_date'     => $booking->end_date,
            'status'       => $booking->status,
            'guests'       => $booking->guests,
            'total_price'  => $booking->total_price,
        ];
    });

    return response()->json([
        'status' => true,
        'property_id' => $propertyId,
        'property_name' => $property->title ?? 'غير محدد',
        'total_bookings' => $data->count(),
        'bookings' => $data
    ]);
}

public function ongoingBookings()
{
    $user = auth('sanctum')->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => __('responses.unauthenticated')
        ], 401);
    }

    $bookings = PropertyBooking::with('property')
        ->where('status', 'pending')
        ->where('user_id', $user->id) // فقط الحجوزات الخاصة بالمستخدم
        ->latest()
        ->get()
        ->map(function ($booking) {
            return $this->addQrToBooking($booking);
        });

    return response()->json([
        'status' => true,
        'message' => __('responses.ongoing_bookings_retrieved'),
        'data' => $bookings
    ]);
}


    /**
     * حذف الحجز
     */
    public function destroy($id)
    {
        $booking = PropertyBooking::find($id);
        if (!$booking) {
            return response()->json(['message' => __('responses.booking_not_found')], 404);
        }

        $booking->delete();
        return response()->json(['message' => __('responses.booking_deleted')]);
    }

    /**
     * توليد QR لكل حجز في النتائج
     */
    private function addQrToBooking($booking)
    {
        $property = $booking->property;

        $qrContent = "🏠 اسم العقار: {$property->title}\n"
            . "📍 العنوان: {$property->address}\n"
            . "👤 الاسم: {$booking->name}\n"
            . "📞 الهاتف: {$booking->phone}\n"
            . "📆 من: {$booking->start_date} إلى {$booking->end_date}\n"
            . "🔐 كلمة المرور: {$booking->access_password}";

        $fileName = 'qr_' . uniqid() . '.png';
        $path = 'public/qrcodes/' . $fileName;
        QrCode::format('png')->size(300)->encoding('UTF-8')->generate($qrContent, storage_path('app/' . $path));

        $bookingData = $booking->toArray();
        $bookingData['qr_code_url'] = url('storage/qrcodes/' . $fileName);
        return $bookingData;
    }
}
