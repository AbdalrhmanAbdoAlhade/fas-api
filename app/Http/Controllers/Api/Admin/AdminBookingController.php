<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\RoomBooking;
use App\Models\Property;
use App\Models\PropertyBooking;
use App\Models\Offer;
use App\Models\OfferBooking;
use App\Services\BookingNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class AdminBookingController extends Controller
{
    /* ============================================================
     |  Helpers
     * ============================================================ */
    private function daysBetween(string $start, string $end): int
    {
        return Carbon::parse($start)->diffInDays(Carbon::parse($end)) ?: 1;
    }

    private function generatePasswords(): array
    {
        return [
            'room_password' => strtoupper('NL' . rand(1000, 9999)) . '@',
            'main_password' => strtoupper('GH' . rand(100, 999)) . '!@' . rand(1, 9),
        ];
    }

    private function safeNotify(callable $callback): void
    {
        try {
            $callback();
        } catch (\Exception $e) {
            Log::warning('Admin booking notification failed: ' . $e->getMessage());
        }
    }

    /* ============================================================
     |  POST /api/admin/bookings/room
     * ============================================================ */
    public function createRoomBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_id'          => 'required|exists:rooms,id',
            'user_id'          => 'nullable|exists:users,id',
            'start_date'       => 'required|date',
            'end_date'         => 'required|date|after:start_date',
            'number_of_rooms'  => 'nullable|integer|min:1',
            'number_of_guests' => 'nullable|integer|min:1',
            'adults'           => 'required|integer|min:0',
            'children'         => 'required|integer|min:0',
            'name'             => 'required|string',
            'date_of_birth'    => 'required|string',
            'national_id'      => 'required|string',
            'email'            => 'required|email',
            'phone'            => 'required|string',
            'title'            => 'nullable|in:Mr,Mrs,Miss',
            'payment_method'   => 'nullable|in:online,on_arrival',
            'status'           => 'nullable|in:pending,confirmed,paid,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $room  = Room::with('hotel')->findOrFail($request->input('room_id'));
        $hotel = $room->hotel;

        if (!$hotel) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.room_not_linked_to_hotel'),
            ], 404);
        }

        $numberOfRooms = $request->input('number_of_rooms', 1);
        $days          = $this->daysBetween($request->input('start_date'), $request->input('end_date'));
        $totalPrice    = $days * $room->price_per_night * $numberOfRooms;

        $passwords = $this->generatePasswords();

        $paymentMethod = $request->input('payment_method', 'on_arrival');
        $defaultStatus = $paymentMethod === 'on_arrival' ? 'confirmed' : 'pending';
        $status        = $request->input('status', $defaultStatus);

        $booking = RoomBooking::create([
            'uuid'               => (string) Str::uuid(),
            'room_id'            => $room->id,
            'hotel_id'           => $hotel->id,
            'user_id'            => $request->input('user_id', Auth::id()),
            'start_date'         => $request->input('start_date'),
            'end_date'           => $request->input('end_date'),
            'number_of_rooms'    => $numberOfRooms,
            'number_of_guests'   => $request->input('number_of_guests', $request->input('adults', 1)),
            'adults'             => $request->input('adults'),
            'children'           => $request->input('children'),
            'total_price'        => $totalPrice,
            'name'               => $request->input('name'),
            'date_of_birth'      => $request->input('date_of_birth', ''),
            'national_id'        => $request->input('national_id', ''),
            'email'              => $request->input('email'),
            'phone'              => $request->input('phone'),
            'title'              => $request->input('title', 'Mr'),
            'room_number'        => $room->room_number ?? '',
            'floor_number'       => $room->floor_number ?? '',
            'room_password'      => $passwords['room_password'],
            'main_password'      => $passwords['main_password'],
            'status'             => $status,
            'payment_method'     => $paymentMethod,
            'paid_at'            => $status === 'paid' ? now() : null,
            'required_documents' => [],
        ]);

        // ✅ إشعار صاحب الفندق بالحجز الجديد
        $this->safeNotify(function () use ($booking) {
            app(BookingNotificationService::class)->notifyOwnerNewRoomBooking($booking);
        });

        return response()->json([
            'status'  => true,
            'message' => __('responses.admin_booking_created'),
            'data'    => $booking->load('room.hotel'),
        ], 201);
    }

    /* ============================================================
     |  POST /api/admin/bookings/property
     * ============================================================ */
    public function createPropertyBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|exists:properties,id',
            'user_id'     => 'nullable|exists:users,id',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after:start_date',
            'guests'      => 'nullable|integer|min:1',
            'name'        => 'required|string',
            'email'       => 'required|email',
            'phone'       => 'required|string',
            'notes'       => 'nullable|string',
            'status'      => 'nullable|in:pending,confirmed,paid,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $property = Property::findOrFail($request->input('property_id'));

        $days       = $this->daysBetween($request->input('start_date'), $request->input('end_date'));
        $totalPrice = $days * $property->price_per_night;

        $booking = PropertyBooking::create([
            'property_id' => $property->id,
            'user_id'     => $request->input('user_id', Auth::id()),
            'start_date'  => $request->input('start_date'),
            'end_date'    => $request->input('end_date'),
            'guests'      => $request->input('guests', 1),
            'total_price' => $totalPrice,
            'status'      => $request->input('status', 'confirmed'),
            'name'        => $request->input('name'),
            'email'       => $request->input('email'),
            'phone'       => $request->input('phone'),
            'notes'       => $request->input('notes'),
        ]);

        // ✅ إشعار صاحب العقار بالحجز الجديد
        $this->safeNotify(function () use ($booking) {
            app(BookingNotificationService::class)->notifyOwnerNewPropertyBooking($booking);
        });

        return response()->json([
            'status'  => true,
            'message' => __('responses.admin_booking_created'),
            'data'    => $booking->load('property'),
        ], 201);
    }

    /* ============================================================
     |  POST /api/admin/bookings/offer
     * ============================================================ */
    public function createOfferBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'offer_id'      => 'required|exists:offers,id',
            'user_id'       => 'nullable|exists:users,id',
            'hotel_id'      => 'nullable|exists:hotels,id',
            'name'          => 'required|string',
            'email'         => 'required|email',
            'phone'         => 'required|string',
            'people_count'  => 'required|integer|min:1',
            'status'        => 'nullable|in:pending,confirmed,paid,cancelled',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $offer = Offer::findOrFail($request->input('offer_id'));

        $unitPrice  = $offer->final_price;
        $totalPrice = $unitPrice * $request->input('people_count');

        $booking = OfferBooking::create([
            'offer_id'     => $offer->id,
            'hotel_id'     => $request->input('hotel_id', $offer->hotel_id),
            'user_id'      => $request->input('user_id', Auth::id()),
            'name'         => $request->input('name'),
            'email'        => $request->input('email'),
            'phone'        => $request->input('phone'),
            'people_count' => $request->input('people_count'),
            'total_price'  => $totalPrice,
            'status'       => $request->input('status', 'confirmed'),
        ]);

        // ملاحظة: إشعار صاحب العرض لسه محتاج service إضافي — تم تعطيله مؤقتًا
        // $this->safeNotify(function () use ($booking) { ... });

        return response()->json([
            'status'  => true,
            'message' => __('responses.admin_booking_created'),
            'data'    => $booking->load('offer'),
        ], 201);
    }
}