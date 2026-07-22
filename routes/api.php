<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\HotelController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomBookingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\HotelReviewController;
use App\Http\Controllers\PropertyTypeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\Api\OfferController;
use App\Http\Controllers\OfferBookingController;
use App\Http\Controllers\PropertyController;
use App\Http\Controllers\PropertyBookingController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\CoordinatorController;
use App\Http\Controllers\TrackingLinkController;
use App\Http\Controllers\FirebaseNotificationController;
use App\Http\Controllers\NotificationLogController;
use App\Http\Controllers\MerchantPaymentSettingController;


/*
|--------------------------------------------------------------------------
| Payments Routes
|--------------------------------------------------------------------------
*/
Route::post('/payments', [PaymentController::class, 'createPaymentForBooking']);
// ✅ webhook بدون auth
Route::post('/payments/webhook', [PaymentController::class, 'handleWebhook']);

// callback للمتصفح (GET)
Route::get('/payment-status', [PaymentController::class, 'paymentCallback']);
/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::post('/registerHotelOwner', [AuthController::class, 'registerHotelOwner']);
Route::middleware('auth:sanctum')->put('/update-user-role/{userId}', [AuthController::class, 'updateUserRole']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/send-otp', [AuthController::class, 'sendOtp']);
Route::post('/update-password', [AuthController::class, 'updatePassword']);
Route::post('/guest-login', [AuthController::class, 'guestLogin']);

Route::middleware(['auth:sanctum'])->get('/users/pending', [AuthController::class, 'pendingUsers']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $request) => $request->user());
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    
});

// Merchant Payment Settings (protected API routes)
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('merchant-payment-settings', MerchantPaymentSettingController::class);
});

/*
|--------------------------------------------------------------------------
| Hotels Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/hotels', [HotelController::class, 'store']);
    Route::post('/hotels/{id}', [HotelController::class, 'update']);
    Route::delete('/hotels/{id}', [HotelController::class, 'destroy']);
});
Route::middleware(['auth:sanctum'])->get('/hotels/pending', [HotelController::class, 'pendingHotels']);
Route::middleware(['auth:api'])->group(function () {
    Route::post('/hotels/{id}/update-status', [HotelController::class, 'updateStatus']);
});
Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/nearby-hotels', [HotelController::class, 'nearbyHotels']);
Route::get('/hotels/{id}', [HotelController::class, 'show']);
Route::get('/hotels-by-stars', [HotelController::class, 'hotelsByStars']);
Route::get('/hotels-by-bookings', [HotelController::class, 'hotelsByBookings']);

/*
|--------------------------------------------------------------------------
| Profile Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);
});

/*
|--------------------------------------------------------------------------
| Room Bookings Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->put('/bookings/{id}/status', [RoomBookingController::class, 'updateBookingStatus']);
Route::get('/bookings/hotel-owner/{userId}', [RoomBookingController::class, 'getBookingsByHotelOwner']);

Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{id}', [RoomController::class, 'show']);
Route::post('/rooms', [RoomController::class, 'store']);
Route::post('/rooms/{id}', [RoomController::class, 'update']);
Route::delete('/rooms/{id}', [RoomController::class, 'destroy']);
Route::get('/hotels/{hotel_id}/rooms', [RoomController::class, 'getRoomsByHotel']);

Route::post('/room-bookings/{id}/pay', [RoomBookingController::class, 'payBooking']);
Route::post('/room-bookings/{id}/cancel', [RoomBookingController::class, 'cancelBooking']);
Route::get('/rooms/{roomId}/bookings', [RoomBookingController::class, 'getRoomBookings']);
Route::get('/bookings', [RoomBookingController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/book-room', [RoomBookingController::class, 'book']);
    Route::put('/bookings/{id}', [RoomBookingController::class, 'update']);
    Route::delete('/bookings/{id}', [RoomBookingController::class, 'destroy']);
});

Route::get('/ongoing-bookings', [RoomBookingController::class, 'ongoingBookings']);
Route::get('/completed-bookings', [RoomBookingController::class, 'completedBookings']);
Route::get('/cancelled-bookings', [RoomBookingController::class, 'cancelledBookings']);

/*
|--------------------------------------------------------------------------
| Search & Filters
|--------------------------------------------------------------------------
*/
Route::get('/search', [SearchController::class, 'search']);
Route::get('/filter-hotels', [SearchController::class, 'filterHotels']);

/*
|--------------------------------------------------------------------------
| Reviews Routes
|--------------------------------------------------------------------------
*/
Route::prefix('reviews')->group(function () {
    Route::post('/', [HotelReviewController::class, 'store']);
    Route::get('{type}/{id}', [HotelReviewController::class, 'index']);
    Route::delete('{id}', [HotelReviewController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Services Routes
|--------------------------------------------------------------------------
*/
// ✅ مسار إنشاء الطلبات محمي بالتوكن
Route::middleware('auth:sanctum')->post('/services/requests', [ServiceController::class, 'createRequest']);

Route::prefix('services')->group(function () {
    // عرض جميع الخدمات
    Route::get('/', [ServiceController::class, 'index']);
    Route::post('/', [ServiceController::class, 'store']);

    // ✅ الطلبات (ضعها قبل {id})
    Route::get('/request', [ServiceController::class, 'getAllRequests']);
    Route::get('/{serviceId}/requests', [ServiceController::class, 'getServiceRequests']);
    Route::put('/requests/{requestId}/status', [ServiceController::class, 'updateRequestStatus']);
    Route::delete('/requests/{requestId}', [ServiceController::class, 'deleteRequest']);

    // بعدين المسارات اللي فيها {id}
    Route::get('/{id}', [ServiceController::class, 'show']);
    Route::post('/{id}', [ServiceController::class, 'update']);
    Route::delete('/{id}', [ServiceController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {

    // جلب طلبات العميل (اليوزر المصادق عليه)
    Route::get('/getClientRequests', [ServiceController::class, 'getClientRequests']);

    // جلب طلبات الفندق المرتبط بالمستخدم المصادق عليه
    Route::get('/getHotelRequests', [ServiceController::class, 'getHotelRequests']);
});

/*
|--------------------------------------------------------------------------
| Property Types Routes
|--------------------------------------------------------------------------
*/
Route::apiResource('property-types', PropertyTypeController::class);

/*
|--------------------------------------------------------------------------
| Companies Routes
|--------------------------------------------------------------------------
*/
Route::prefix('companies')->group(function () {
    Route::get('/', [CompanyController::class, 'index']);
    Route::get('/{id}', [CompanyController::class, 'show']);
    Route::middleware('auth:sanctum')->post('/', [CompanyController::class, 'store']);
    Route::middleware('auth:sanctum')->post('/{id}', [CompanyController::class, 'update']);
    Route::middleware('auth:sanctum')->delete('/{id}', [CompanyController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Offers & Offer Bookings Routes
|--------------------------------------------------------------------------
*/
Route::prefix('offers')->group(function () {
    Route::get('/', [OfferController::class, 'index']);
    Route::get('/{id}', [OfferController::class, 'show']);
    Route::post('/', [OfferController::class, 'store'])->middleware('auth:sanctum');
    Route::post('/{id}', [OfferController::class, 'update'])->middleware('auth:sanctum');
    Route::delete('/{id}', [OfferController::class, 'destroy'])->middleware('auth:sanctum');
});

Route::prefix('bookings')->group(function () {
    Route::post('offers', [OfferBookingController::class, 'store']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('offers', [OfferBookingController::class, 'index']);
        Route::get('offers/{id}', [OfferBookingController::class, 'show']);
        Route::put('offers/{id}', [OfferBookingController::class, 'update']);
        Route::delete('offers/{id}', [OfferBookingController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Properties & Property Bookings Routes
|--------------------------------------------------------------------------
*/
Route::get('/properties', [PropertyController::class, 'index']);
Route::get('properties/{id}', [PropertyController::class, 'show']);
Route::get('properties/by-bookings', [PropertyController::class, 'propertiesByBookings']);
Route::get('properties/by-stars', [PropertyController::class, 'propertiesByStars']);
Route::get('properties/nearby', [PropertyController::class, 'nearbyProperties']);

    Route::get('properties/{id}/bookings', [PropertyBookingController::class, 'getPropertyBookings']);
    Route::get('/property-bookings/ongoing', [PropertyBookingController::class, 'ongoingBookings']);

Route::middleware('auth:sanctum')->prefix('properties')->group(function () {
    Route::post('/', [PropertyController::class, 'store']);
    Route::post('/{id}', [PropertyController::class, 'update']);
    Route::delete('/{id}', [PropertyController::class, 'destroy']);

});

Route::prefix('property-bookings')->group(function () {
    Route::post('/', [PropertyBookingController::class, 'book']);
    Route::get('/', [PropertyBookingController::class, 'index']);
    Route::get('/{id}', [PropertyBookingController::class, 'show']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{id}', [PropertyBookingController::class, 'update']);
        Route::delete('/{id}', [PropertyBookingController::class, 'destroy']);
         Route::post('cancel/{id}', [PropertyBookingController::class, 'cancel']);
    });



Route::middleware('auth:sanctum')->group(function () {
    Route::get('/property-bookings/ongoing', [PropertyBookingController::class, 'ongoingBookings']);
});
});

/*
|--------------------------------------------------------------------------
| Coordinators Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->get('/my-links', [CoordinatorController::class, 'myLinks']);
Route::post('/coordinators', [CoordinatorController::class, 'store']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/coordinators', [CoordinatorController::class, 'index']);
    Route::get('/coordinators/{id}', [CoordinatorController::class, 'show']);
    Route::put('/coordinators/{id}', [CoordinatorController::class, 'update']);
    Route::delete('/coordinators/{id}', [CoordinatorController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Tracking Links Routes
|--------------------------------------------------------------------------
*/
Route::get('/tracking/{tracking_link_id}', [TrackingLinkController::class, 'track']);
Route::post('/tracking_links_update/{id}', [TrackingLinkController::class, 'trackingupdate']);
Route::apiResource('tracking_links', TrackingLinkController::class)->only(['store', 'index', 'show', 'destroy', 'update']);
Route::put('/archive/{tracking_link_id}', [TrackingLinkController::class, 'archive']);
Route::post('/archive/{tracking_link_id}', [TrackingLinkController::class, 'archive']);
Route::post('/unarchive/{tracking_link_id}', [TrackingLinkController::class, 'unarchive']);

/*
|--------------------------------------------------------------------------
| Notifications Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/save-device-token', [FirebaseNotificationController::class, 'saveDeviceToken']);

});
    Route::post('/send-firebase-notification', [FirebaseNotificationController::class, 'send']);
    Route::prefix('notifications')->group(function () {
    Route::get('/', [NotificationLogController::class, 'index']);      // عرض كل الإشعارات
    Route::get('/{id}', [NotificationLogController::class, 'show']);   // عرض إشعار واحد
    Route::delete('/{id}', [NotificationLogController::class, 'destroy']); // حذف إشعار
});
