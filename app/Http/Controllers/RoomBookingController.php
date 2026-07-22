<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Hotel;
use App\Models\Offer;
use App\Models\RoomBooking;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

        

class RoomBookingController extends Controller
{

public function getBookingsByHotelOwner($userId)
{
    // تحقق من وجود المستخدم
    $owner = User::find($userId);
    if (!$owner) {
        return response()->json([
            'status' => false,
            'message' => __('responses.user_not_found')
        ], 404);
    }

    // جلب كل الحجوزات المرتبطة بفنادق هذا المستخدم
    $bookings = \App\Models\RoomBooking::whereHas('room.hotel', function ($query) use ($userId) {
        $query->where('user_id', $userId);
    })
    ->with(['room.hotel'])
    ->latest()
    ->get();

    if ($bookings->isEmpty()) {
        return response()->json([
            'status' => true,
            'message' => __('responses.no_bookings_for_hotel'),
            'data' => []
        ]);
    }

    // تجهيز البيانات بشكل منسق
    $data = $bookings->map(function ($booking) {
        return [
            'booking_id' => $booking->id,
            'guest_name' => $booking->name,
            'guest_phone' => $booking->phone,
            'status' => $booking->status,
            'start_date' => $booking->start_date,
            'end_date' => $booking->end_date,
            'room_number' => $booking->room_number,
            'hotel_name' => $booking->room->hotel->name ?? 'غير معروف',
            'hotel_id' => $booking->room->hotel->id ?? null,
        ];
    });

    return response()->json([
        'status' => true,
        'owner_id' => $userId,
        'total_bookings' => $data->count(),
        'data' => $data
    ]);
}

public function book(Request $request)
{
    // 1. التحقق من البيانات (Validation)
    $validator = Validator::make($request->all(), [
        'room_id'              => 'required|exists:rooms,id', // أصبح مطلوباً لأنه حجز غرف فقط
        'required_documents.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
        'start_date'           => 'required|date|after_or_equal:today',
        'end_date'             => 'required|date|after:start_date',
        'number_of_rooms'      => 'nullable|integer|min:1',
        'number_of_guests'     => 'nullable|integer|min:1',
        'adults'               => 'required|integer|min:0',
        'children'             => 'required|integer|min:0',
        'name'                 => 'required|string',
        'date_of_birth'        => 'required|string',
        'national_id'          => 'required|string',
        'email'                => 'required|email',
        'phone'                => 'required|string',
        'title'                => 'required|in:Mr,Mrs,Miss',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // 2. جلب بيانات الغرفة والفندق المرتبط بها
    $room = Room::with('hotel')->findOrFail($request->room_id);
    $hotel = $room->hotel;

    if (!$hotel) {
        return response()->json(['message' => __('responses.room_not_linked_to_hotel')], 404);
    }

    // 3. حساب السعر والبيانات الأساسية
    $numberOfRooms = $request->number_of_rooms ?? 1;
    $days = Carbon::parse($request->start_date)->diffInDays(Carbon::parse($request->end_date)) ?: 1;
    $totalPrice = $days * $room->price_per_night * $numberOfRooms;

    // 4. تحميل الملفات (إن وجدت)
    $uploadedDocuments = [];
    if ($request->hasFile('required_documents')) {
        foreach ($request->file('required_documents') as $file) {
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/required_documents', $filename);
            $uploadedDocuments[] = url('storage/required_documents/' . $filename);
        }
    }

    // 5. توليد كلمات المرور العشوائية
    $roomPassword = strtoupper('NL' . rand(1000, 9999)) . '@';
    $mainPassword = strtoupper('GH' . rand(100, 999)) . '!@' . rand(1, 9);

    // 6. إنشاء الحجز في قاعدة البيانات
    $booking = RoomBooking::create([
        'room_id'            => $room->id,
        'hotel_id'           => $hotel->id,
        'user_id'            => Auth::id(),
        'start_date'         => $request->start_date,
        'end_date'           => $request->end_date,
        'number_of_rooms'    => $numberOfRooms,
        'number_of_guests'   => $request->number_of_guests,
        'adults'             => $request->adults,
        'children'           => $request->children,
        'total_price'        => $totalPrice,
        'name'               => $request->name,
        'date_of_birth'      => $request->date_of_birth,
        'national_id'        => $request->national_id,
        'email'              => $request->email,
        'phone'              => $request->phone,
        'title'              => $request->title,
        'room_number'        => $room->room_number, // سحب مباشر من الغرفة
        'floor_number'       => $room->floor_number, // سحب مباشر من الغرفة
        'room_password'      => $roomPassword,
        'main_password'      => $mainPassword,
        'status'             => 'pending',
        'required_documents' => $uploadedDocuments,
    ]);

    // 7. استدعاء سيرفيس الدفع (EdfaPay)
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
            'redirect_url' => $paymentResult['redirect_url']
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => __('responses.booking_creation_payment_failed', ['error' => $e->getMessage()])
        ], 500);
    }
}



 private function sendWhatsappMessage($phone, $message)
{
    $apiUrl = 'https://your-whatsapp-api.com/send'; // ضع هنا رابط API الإرسال
    $apiToken = 'YOUR_API_TOKEN'; // التوكن الخاص بك

    try {
        Http::withHeaders([
            'Authorization' => "Bearer $apiToken"
        ])->post($apiUrl, [
            'phone' => $phone,
            'message' => $message
        ]);
    } catch (\Exception $e) {
        \Log::error('فشل إرسال رسالة واتساب: ' . $e->getMessage());
    }
}



public function update(Request $request, $id)
{
    $booking = RoomBooking::findOrFail($id);

    $validator = Validator::make($request->all(), [
        'room_id' => 'exists:rooms,id',
        'start_date' => 'date|after_or_equal:today',
        'end_date' => 'date|after:start_date',
        'number_of_rooms' => 'integer|min:1',
        'number_of_guests' => 'integer|min:1',
        'adults' => 'integer|min:0',
        'children' => 'integer|min:0',
        'name' => 'string',
        'national_id' => 'required|string',
        'email' => 'email',
        'phone' => 'string',
        'room_number' => 'string',
        'floor_number' => 'string',

    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $booking->fill($request->only([
        'room_id', 'start_date', 'end_date', 'number_of_rooms',
        'number_of_guests', 'adults', 'children', 'name', 'national_id',
        'email', 'phone', 'room_number', 'floor_number'
    ]));

    if ($booking->isDirty(['room_id', 'start_date', 'end_date', 'number_of_rooms'])) {
        $room = Room::with('hotel')->find($booking->room_id);
        $start = Carbon::parse($booking->start_date);
        $end = Carbon::parse($booking->end_date);
        $days = $start->diffInDays($end);
        $booking->total_price = $days * $room->price_per_night * $booking->number_of_rooms;
    }

    $booking->save();

    $room = Room::with('hotel')->find($booking->room_id);
    $mapUrl = "https://www.google.com/maps?q={$room->hotel->latitude},{$room->hotel->longitude}";

    $qrContent = "🏨 اسم الفندق: {$room->hotel->name}\n"
        . "🌍 الإحداثيات: {$room->hotel->latitude}, {$room->hotel->longitude}\n"
        . "🗺️ رابط الخريطة: {$mapUrl}\n"
        . "👤 الاسم: {$booking->name}\n"
        . "📞 الهاتف: {$booking->phone}\n"
        . "👨👩 البالغون: {$booking->adults}\n"
        . "👦👧 الأطفال: {$booking->children}\n"
        . "👤 رقم الهوية: {$booking->national_id}\n"
        . "🕒 تسجيل الوصول: {$booking->start_date}\n"
        . "🚪 تسجيل المغادرة: {$booking->end_date}\n"
        . "🔐 باسورد الغرفة: {$booking->room_password}\n"
        . "🔐 الباسورد الرئيسي: {$booking->main_password}";

    $fileName = 'qr_' . uniqid() . '.png';
    $path = 'public/qrcodes/' . $fileName;
    QrCode::format('png')->size(300)->generate($qrContent, storage_path('app/' . $path));

    return response()->json([
        'message' => __('responses.updated_successfully'),
        'ticket' => [
            'اسم الفندق' => $room->hotel->name,
            'إحداثيات الفندق' => "{$room->hotel->latitude}, {$room->hotel->longitude}",
            'رابط الخريطة' => $mapUrl,
            'اسم' => $booking->name,
            'رقم الهاتف' => $booking->phone,
            'تسجيل الوصول' => $booking->start_date,
            'تسجيل المغادرة' => $booking->end_date,
            'عدد الضيوف' => $booking->number_of_guests,
            'رقم الغرفة' => $booking->room_number,
            'رقم الدور' => $booking->floor_number,
            'باسورد الغرفة' => $booking->room_password,
            'الباسورد الرئيسي' => $booking->main_password,
            'qr_code_url' => url('storage/qrcodes/' . $fileName)
        ]
    ]);
}

public function payBooking($id)
{
    $booking = RoomBooking::find($id);

    if (!$booking) {
        return response()->json(['message' => __('responses.booking_not_found')], 404);
    }

    $booking->status = 'paid';
    $booking->save();

    return response()->json(['message' => __('responses.payment_successful'), 'الحالة' => $booking->status]);
}

public function cancelBooking()
{
    $user = auth('sanctum')->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => __('responses.unauthenticated')
        ], 401);
    }

    // جلب الحجز الخاص بالمستخدم والحالة pending فقط
    $booking = RoomBooking::where('user_id', $user->id)
                          ->where('status', 'pending')
                          ->first();

    if (!$booking) {
        return response()->json([
            'message' => __('responses.no_pending_booking_to_cancel')
        ], 404);
    }

    // التأكد أن الحجز لم يتم تأكيده أو دفعه
    if (in_array($booking->status, ['paid', 'completed'])) {
        return response()->json([
            'message' => __('responses.cannot_cancel_confirmed_booking')
        ], 403);
    }

    // تحديث الحالة إلى cancelled
    $booking->status = 'cancelled';
    $booking->save();

    return response()->json([
        'message' => __('responses.booking_cancelled_successfully'),
        'status' => $booking->status
    ]);
}




    public function destroy($id)
    {
        $booking = RoomBooking::find($id);

        if (!$booking) {
            return response()->json(['error' => __('responses.booking_not_found')], 404);
        }

        $booking->delete();

        return response()->json(['message' => __('responses.booking_cancelled_successfully')]);
    }

public function index(Request $request)
{
    $user = auth('sanctum')->user();

    // التحقق من تسجيل الدخول فقط
    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => __('responses.unauthenticated')
        ], 422);
    }

    // إنشاء الاستعلام مع العلاقات
    $query = RoomBooking::with('room.hotel');

    // ✅ فلتر حسب الحالة (status)
    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    // ✅ باجينيشن (عدد النتائج في الصفحة يمكن تغييره من خلال per_page)
    $perPage = $request->get('per_page', 5);
    $bookings = $query->latest()->paginate($perPage);

    // ✅ إضافة QR لكل حجز داخل الصفحة الحالية
    $bookings->getCollection()->transform(function ($booking) {
        return $this->addQrToBooking($booking);
    });

    // ✅ إرجاع النتيجة
    return response()->json([
        'status' => true,
        'data' => $bookings
    ]);
}

public function getRoomBookings($roomId)
{
    // تأكد أن الغرفة موجودة
    $room = Room::find($roomId);
    if (!$room) {
        return response()->json([
            'status' => false,
            'message' => __('responses.room_not_found')
        ], 404);
    }

    // جلب الحجوزات الخاصة بالغرفة (المعلقة أو المدفوعة فقط)
    $bookings = RoomBooking::where('room_id', $roomId)
        ->whereIn('status', ['confirmed', 'paid'])
        ->select('id', 'start_date', 'end_date', 'status', 'name')
        ->orderBy('start_date', 'asc')
        ->get();

    if ($bookings->isEmpty()) {
        return response()->json([
            'status' => true,
            'message' => __('responses.no_bookings_for_room'),
            'data' => []
        ]);
    }

    // تجهيز التواريخ والبيانات
    $data = $bookings->map(function ($booking) {
        return [
            'booking_id' => $booking->id,
            'name' => $booking->name,
            'start_date' => $booking->start_date,
            'end_date' => $booking->end_date,
            'status' => $booking->status
        ];
    });

    return response()->json([
        'status' => true,
        'room_id' => $roomId,
        'room_name' => $room->name ?? 'غير محددة',
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

    $bookings = RoomBooking::with('room.hotel')
        ->where('status', 'pending')      // فقط الحجوزات قيد التنفيذ
        ->where('user_id', $user->id)     // فقط الحجوزات الخاصة بالمستخدم الحالي
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


public function completedBookings()
{
    $user = auth('sanctum')->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => __('responses.unauthenticated')
        ], 401);
    }

    $bookings = RoomBooking::with('room.hotel')
        ->where('status', 'paid')
        ->where('user_id', $user->id)  // فقط للمستخدم الحالي
        ->latest()
        ->get()
        ->map(function ($booking) {
            return $this->addQrToBooking($booking);
        });

    return response()->json([
        'status' => true,
        'message' => __('responses.completed_bookings_retrieved'),
        'data' => $bookings
    ]);
}


public function cancelledBookings()
{
    $user = auth('sanctum')->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => __('responses.unauthenticated')
        ], 401);
    }

    $bookings = RoomBooking::with('room.hotel')
        ->where('status', 'cancelled')
        ->where('user_id', $user->id)  // فقط للمستخدم الحالي
        ->latest()
        ->get()
        ->map(function ($booking) {
            return $this->addQrToBooking($booking);
        });

    return response()->json([
        'status' => true,
        'message' => __('responses.cancelled_bookings_retrieved'),
        'data' => $bookings
    ]);
}


// دالة مساعدة لإنشاء QR code وإضافته لبيانات الحجز
private function addQrToBooking($booking)
{
    $room = $booking->room;
    $hotel = $room->hotel ?? null;

    if (!$hotel) {
        return $booking;
    }

    $mapUrl = "https://www.google.com/maps?q={$hotel->latitude},{$hotel->longitude}";

    $qrContent = "🏨 اسم الفندق: {$hotel->name}\n"
        . "🌍 الإحداثيات: {$hotel->latitude}, {$hotel->longitude}\n"
        . "🗺️ رابط الخريطة: {$mapUrl}\n"
        . "👤 الاسم: {$booking->name}\n"
        . "📞 الهاتف: {$booking->phone}\n"
        . "🛏️ رقم الغرفة: {$booking->room_number}\n"
        . "👤 رقم الهوية: {$booking->national_id}\n"
        . "📶 رقم الدور: {$booking->floor_number}\n"
        . "👨👩 البالغون: {$booking->adults}\n"
        . "👦👧 الأطفال: {$booking->children}\n"
        . "🕒 تسجيل الوصول: {$booking->start_date}\n"
        . "🚪 تسجيل المغادرة: {$booking->end_date}\n"
        . "🔐 باسورد الغرفة: {$booking->room_password}\n"
        . "🔐 الباسورد الرئيسي: {$booking->main_password}";

    $fileName = 'qr_' . uniqid() . '.png';
    $path = 'public/qrcodes/' . $fileName;

    QrCode::format('png')->size(300)->encoding('UTF-8')->generate($qrContent, storage_path('app/' . $path));

    // إضافة بيانات QR code إلى بيانات الحجز
    $bookingData = $booking->toArray();
    $bookingData['qr_code_url'] = url('storage/qrcodes/' . $fileName);
    $bookingData['qr_content'] = $qrContent; // اختياري: إذا أردت إرسال محتوى QR كنص أيضاً

    return $bookingData;
}


public function updateBookingStatus(Request $request, $id)
{
    $user = auth('sanctum')->user();

    if (!$user) {
        return response()->json([
            'status' => false,
            'message' => __('responses.unauthenticated')
        ], 401);
    }

    // التحقق من المدخلات
    $validator = Validator::make($request->all(), [
        'status' => 'required|in:pending,confirmed,cancelled'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'errors' => $validator->errors()
        ], 422);
    }

    // جلب الحجز
    $booking = RoomBooking::with('room.hotel')->find($id);

    if (!$booking) {
        return response()->json([
            'status' => false,
            'message' => __('responses.booking_not_found')
        ], 404);
    }

    // التحقق من صلاحيات المستخدم
    $hotelOwnerId = $booking->room?->hotel?->user_id;

    if ($user->role !== 'admin' && $user->id !== $hotelOwnerId) {
        return response()->json([
            'status' => false,
            'message' => __('responses.unauthorized_booking_status_update')
        ], 403);
    }

    // تعديل الحالة
    $booking->status = $request->status;
    $booking->save();

    return response()->json([
        'status' => true,
        'message' => __('responses.status_updated_successfully'),
        'data' => [
            'booking_id' => $booking->id,
            'new_status' => $booking->status,
            'hotel_name' => $booking->room?->hotel?->name ?? 'غير محدد'
        ]
    ]);
}


}

