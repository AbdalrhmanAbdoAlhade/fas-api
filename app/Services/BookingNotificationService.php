<?php

namespace App\Services;

use App\Models\RoomBooking;
use App\Models\PropertyBooking;
use App\Models\OfferBooking;
use Illuminate\Support\Facades\Log;

class BookingNotificationService
{
    protected FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    /* ============================================================
     |  إشعارات صاحب الفندق / العقار / الشركة
     * ============================================================ */

    /**
     * إشعار صاحب الفندق عند حجز جديد
     */
    public function notifyOwnerNewRoomBooking(RoomBooking $booking): bool
    {
        try {
            $booking->loadMissing('room.hotel.user');
            $hotel = $booking->room?->hotel;
            $owner = $hotel?->user;

            if (!$owner) {
                Log::info('RoomBooking notification skipped: no hotel owner', ['booking_id' => $booking->id]);
                return false;
            }

            $title = 'حجز جديد في فندقك';
            $body  = "تم حجز غرفة في {$hotel->name} بتاريخ {$booking->start_date} من {$booking->name}";

            return $this->firebase->sendToUser(
                $owner->id,
                $title,
                $body,
                [
                    'type'       => 'new_room_booking',
                    'booking_id' => (string) $booking->id,
                    'room_id'    => (string) $booking->room_id,
                    'hotel_id'   => (string) $booking->hotel_id,
                ]
            );

        } catch (\Exception $e) {
            Log::error('notifyOwnerNewRoomBooking failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * إشعار صاحب العقار عند حجز جديد
     */
    public function notifyOwnerNewPropertyBooking(PropertyBooking $booking): bool
    {
        try {
            $booking->loadMissing('property.user');
            $property = $booking->property;
            $owner    = $property?->user;

            if (!$owner) {
                return false;
            }

            $title = 'حجز جديد على عقارك';
            $body  = "تم حجز {$property->title} بتاريخ {$booking->start_date} من {$booking->name}";

            return $this->firebase->sendToUser(
                $owner->id,
                $title,
                $body,
                [
                    'type'        => 'new_property_booking',
                    'booking_id'  => (string) $booking->id,
                    'property_id' => (string) $booking->property_id,
                ]
            );

        } catch (\Exception $e) {
            Log::error('notifyOwnerNewPropertyBooking failed: ' . $e->getMessage());
            return false;
        }
    }

    /* ============================================================
     |  إشعارات العميل
     * ============================================================ */

    /**
     * إشعار العميل عند تأكيد الدفع (للدفع عند الوصول)
     */
    public function notifyCustomerPaymentConfirmed(RoomBooking $booking): bool
    {
        try {
            if (!$booking->user_id) {
                return false;
            }

            $booking->loadMissing('room.hotel');
            $hotel = $booking->room?->hotel;

            $title = 'تم تأكيد دفعك';
            $body  = "تم استلام مبلغ {$booking->total_price} ريال لحجزك في {$hotel->name}. رمز الدخول: {$booking->main_password}";

            return $this->firebase->sendToUser(
                $booking->user_id,
                $title,
                $body,
                [
                    'type'       => 'payment_confirmed',
                    'booking_id' => (string) $booking->id,
                    'status'     => $booking->status,
                ]
            );

        } catch (\Exception $e) {
            Log::error('notifyCustomerPaymentConfirmed failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * إشعار العميل عند تأكيد الدفع (للدفع أونلاين)
     */
    public function notifyCustomerOnlinePaymentConfirmed(RoomBooking $booking): bool
    {
        try {
            if (!$booking->user_id) {
                return false;
            }

            $booking->loadMissing('room.hotel');
            $hotel = $booking->room?->hotel;

            $title = 'تم تأكيد حجزك';
            $body  = "تم دفع حجزك في {$hotel->name} بنجاح. رمز الدخول: {$booking->main_password}";

            return $this->firebase->sendToUser(
                $booking->user_id,
                $title,
                $body,
                [
                    'type'       => 'online_payment_confirmed',
                    'booking_id' => (string) $booking->id,
                ]
            );

        } catch (\Exception $e) {
            Log::error('notifyCustomerOnlinePaymentConfirmed failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * إشعار العميل عند إلغاء حجزه
     */
    public function notifyCustomerBookingCancelled(RoomBooking $booking): bool
    {
        try {
            if (!$booking->user_id) {
                return false;
            }

            $title = 'تم إلغاء حجزك';
            $body  = "تم إلغاء الحجز رقم {$booking->id} بنجاح.";

            return $this->firebase->sendToUser(
                $booking->user_id,
                $title,
                $body,
                [
                    'type'       => 'booking_cancelled',
                    'booking_id' => (string) $booking->id,
                ]
            );

        } catch (\Exception $e) {
            Log::error('notifyCustomerBookingCancelled failed: ' . $e->getMessage());
            return false;
        }
    }
}