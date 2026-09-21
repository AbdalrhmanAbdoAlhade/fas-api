<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OfferBooking;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class OfferBookingController extends Controller
{
    public function index()
    {
        // ✅ تحميل الفنادق المرتبطة بالحجوزات
        $bookings = OfferBooking::with(['offer', 'hotel', 'selectedHotel'])->latest()->get();
        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_id'                        => 'required|exists:offers,id',
            'selected_hotel_id'               => 'nullable|exists:hotels,id', // ✅ حقل الفندق المختار
            'name'                            => 'required|string',
            'date_of_birth'                   => 'required|string',
            'national_id'                     => 'required|string',
            'email'                           => 'required|email',
            'phone'                           => 'required|string',
            'required_documents.*'            => 'nullable|file|mimes:jpg,jpeg,png,pdf,doc,docx|max:5120',
            'selected_options'                => 'nullable|array',
            'selected_options.*.name'         => 'required_with:selected_options|string',
            'selected_options.*.price'        => 'required_with:selected_options|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // ✅ جلب العرض مع الفنادق المرتبطة به للتحقق
        $offer = Offer::with(['hotel', 'hotels'])->findOrFail($request->offer_id);
        $defaultHotel = $offer->hotel;

        $hotelPrice = 0;
        $selectedHotelId = $request->selected_hotel_id;

        // ✅ التحقق من الفندق المختار وحساب سعره
        if ($selectedHotelId) {
            $selectedHotel = $offer->hotels->where('id', $selectedHotelId)->first();
            
            if (!$selectedHotel) {
                return response()->json([
                    'status' => false,
                    'message' => 'الفندق المختار غير متاح أو غير مرتبط بهذا العرض'
                ], 422);
            }
            
            // جلب السعر من الـ Pivot Table
            $hotelPrice = $selectedHotel->pivot->price;
        }

        // رفع الملفات
        // ✅ إصلاح: التعامل مع الحالتين — ملف واحد (object) أو عدة ملفات (array)
        $uploadedDocuments = [];
        if ($request->hasFile('required_documents')) {
            $files = $request->file('required_documents');
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                if (!$file || !$file->isValid()) {
                    continue;
                }
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->storeAs('public/required_documents', $filename);
                $uploadedDocuments[] = url('storage/required_documents/' . $filename);
            }
        }

        $roomPassword = strtoupper('OF' . rand(1000, 9999)) . '@';
        $mainPassword = strtoupper('BO' . rand(100, 999)) . '!@' . rand(1, 9);

        // ✅ حساب السعر الكلي: السعر الأساسي + سعر الفندق المختار + الإضافات
        $totalPrice = $offer->price + $hotelPrice; 
        
        if ($request->has('selected_options')) {
            foreach ($request->selected_options as $option) {
                $totalPrice += $option['price'];
            }
        }

        $booking = OfferBooking::create([
            'offer_id'             => $offer->id,
            'hotel_id'             => $defaultHotel->id ?? null,
            'selected_hotel_id'    => $selectedHotelId,         // ✅ الفندق المختار
            'hotel_price_snapshot' => $hotelPrice,              // ✅ حفظ السعر كلقطة تاريخية
            'user_id'              => Auth::id(),
            'total_price'          => $totalPrice,
            'name'                 => $request->name,
            'date_of_birth'        => $request->date_of_birth,
            'national_id'          => $request->national_id,
            'email'                => $request->email,
            'phone'                => $request->phone,
            'room_password'        => $roomPassword,
            'main_password'        => $mainPassword,
            'status'               => 'pending',
            'required_documents'   => $uploadedDocuments,
            'selected_options'     => $request->selected_options,
        ]);

        // استدعاء بوابة الدفع
        try {
            $paymentResult = app(\App\Services\EdfaPayService::class)->initiatePayment([
                'booking_id'   => $booking->id,
                'booking_type' => get_class($booking),
                'amount'       => $totalPrice, // ✅ السعر الإجمالي الجديد
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
                'booking'      => $booking->load('selectedHotel'), // ✅ إرجاع بيانات الفندق المختار
                'offer'        => [
                    'id'                 => $offer->id,
                    'name'               => $offer->name,
                    'description'        => $offer->description,
                    'price'              => $offer->price,
                    'departure_time'     => $offer->departure_time,
                    'return_time'        => $offer->return_time,
                    'features'           => $offer->features,
                    'people_count'       => $offer->people_count,
                    'transportation'     => $offer->transportation,
                    'program'            => $offer->program,
                    'path'               => $offer->path,
                    'cover_images'       => $offer->cover_images,
                    'images'             => $offer->images,
                    'required_documents' => $offer->required_documents,
                    'options'            => $offer->options,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.booking_creation_payment_failed', ['error' => $e->getMessage()])
            ], 500);
        }
    }

    public function show($id)
    {
        // ✅ تحميل الفندق المختار مع الحجز
        $booking = OfferBooking::with(['offer', 'hotel', 'selectedHotel'])->findOrFail($id);
        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $booking = OfferBooking::findOrFail($id);
        $user = auth('sanctum')->user();

        if ($user) {
            if ($user->role === 'company') {
                $companyId = $user->company->id ?? null;
                $offerCompanyId = $booking->offer->company_id ?? null;

                if ($offerCompanyId !== $companyId) {
                    return response()->json([
                        'message' => __('responses.unauthorized_offer_booking_update')
                    ], 403);
                }
            }

            if (!in_array($user->role, ['admin', 'company'])) {
                return response()->json([
                    'message' => __('responses.unauthorized_offer_booking_update')
                ], 403);
            }
        } else {
            return response()->json([
                'message' => __('responses.login_to_update_offer_booking')
            ], 401);
        }

        $data = $request->validate([
            'status' => 'nullable|in:pending,confirmed,paid,cancelled',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'name' => 'nullable|string',
        ]);

        $booking->update($data);

        return response()->json([
            'message' => __('responses.booking_updated'),
            'booking' => $booking,
        ]);
    }

    public function destroy($id)
    {
        $booking = OfferBooking::findOrFail($id);
        $booking->delete();

        return response()->json(['message' => __('responses.booking_deleted')]);
    }
}