<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\OfferBooking;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


class OfferBookingController extends Controller
{
    public function index()
    {
        $bookings = OfferBooking::with(['offer', 'hotel'])->latest()->get();
        return response()->json($bookings);
    }

public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'offer_id'                        => 'required|exists:offers,id',
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

    $offer = Offer::with('hotel')->findOrFail($request->offer_id);
    $hotel = $offer->hotel;

    // رفع الملفات
    $uploadedDocuments = [];
    if ($request->hasFile('required_documents')) {
        foreach ($request->file('required_documents') as $file) {
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/required_documents', $filename);
            $uploadedDocuments[] = url('storage/required_documents/' . $filename);
        }
    }

    $roomPassword = strtoupper('OF' . rand(1000, 9999)) . '@';
    $mainPassword = strtoupper('BO' . rand(100, 999)) . '!@' . rand(1, 9);

    // حساب السعر الكلي
    $totalPrice = $offer->price;
    if ($request->has('selected_options')) {
        foreach ($request->selected_options as $option) {
            $totalPrice += $option['price'];
        }
    }

    $booking = OfferBooking::create([
        'offer_id'           => $offer->id,
        'hotel_id'           => $hotel->id ?? null,
        'user_id'            => Auth::id(), // ✅ من التوكن
        'total_price'        => $totalPrice,
        'name'               => $request->name,
        'date_of_birth'      => $request->date_of_birth,
        'national_id'        => $request->national_id,
        'email'              => $request->email,
        'phone'              => $request->phone,
        'room_password'      => $roomPassword,
        'main_password'      => $mainPassword,
        'status'             => 'pending',
        'required_documents' => $uploadedDocuments,
        'selected_options'   => $request->selected_options,
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
            'booking'      => $booking,
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
        $booking = OfferBooking::with(['offer', 'hotel'])->findOrFail($id);
        return response()->json($booking);
    }



    public function update(Request $request, $id)
    {
        $booking = OfferBooking::findOrFail($id);

        $user = auth('sanctum')->user(); // المستخدم الحالي (مدير أو شركة أو عميل)

        // التحقق من صلاحية التعديل
        if ($user) {
            // لو المستخدم شركة، لازم يكون هو صاحب العرض
            if ($user->role === 'company') {
                $companyId = $user->company->id ?? null; // الشركة التابعة للمستخدم
                $offerCompanyId = $booking->offer->company_id ?? null;

                if ($offerCompanyId !== $companyId) {
                    return response()->json([
                        'message' => __('responses.unauthorized_offer_booking_update')
                    ], 403);
                }
            }


            // لو المستخدم عادي (ليس أدمن ولا شركة) يمنع التعديل
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

        // التحقق من القيم المرسلة
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
