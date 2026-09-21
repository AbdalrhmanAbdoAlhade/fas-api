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
use App\Http\Controllers\Api\Admin\BlockingController;
use App\Http\Controllers\Api\Admin\EmployeeController;
use App\Http\Controllers\Api\Admin\AdminBookingController;

/*
|--------------------------------------------------------------------------
| Payments Routes
|--------------------------------------------------------------------------
*/
Route::post('/payments', [PaymentController::class, 'createPaymentForBooking']);
Route::post('/payments/webhook', [PaymentController::class, 'handleWebhook']);
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

// Merchant Payment Settings
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('merchant-payment-settings', MerchantPaymentSettingController::class);
});

/*
|--------------------------------------------------------------------------
| Hotels Routes
|--------------------------------------------------------------------------
*/
// ✅ pending + update-status (قبل {id})
Route::middleware('auth:sanctum')->get('/hotels/pending', [HotelController::class, 'pendingHotels']);
Route::middleware('auth:sanctum')->post('/hotels/{id}/update-status', [HotelController::class, 'updateStatus']);

// القراءة العامة
Route::get('/hotels', [HotelController::class, 'index']);
Route::get('/nearby-hotels', [HotelController::class, 'nearbyHotels']);
Route::get('/hotels-by-stars', [HotelController::class, 'hotelsByStars']);
Route::get('/hotels-by-bookings', [HotelController::class, 'hotelsByBookings']);
Route::get('/hotels/{id}', [HotelController::class, 'show']);

// CRUD (محمي)
Route::middleware(['auth:sanctum', 'permission:hotels.create,hotel_owner,company_owner'])
    ->post('/hotels', [HotelController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:hotels.update,hotel_owner,company_owner'])
    ->post('/hotels/{id}', [HotelController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:hotels.delete,hotel_owner,company_owner'])
    ->delete('/hotels/{id}', [HotelController::class, 'destroy']);

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
Route::post('room-bookings/{id}/mark-as-paid', [RoomBookingController::class, 'markAsPaid'])
    ->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->put('/bookings/{id}/status', [RoomBookingController::class, 'updateBookingStatus']);
Route::get('/bookings/hotel-owner/{userId}', [RoomBookingController::class, 'getBookingsByHotelOwner']);

/*
|--------------------------------------------------------------------------
| Rooms Routes
|--------------------------------------------------------------------------
*/
Route::get('/rooms', [RoomController::class, 'index']);
Route::get('/rooms/{id}', [RoomController::class, 'show']);
Route::get('/hotels/{hotel_id}/rooms', [RoomController::class, 'getRoomsByHotel']);

Route::middleware(['auth:sanctum', 'permission:hotels.create,hotel_owner,company_owner'])
    ->post('/rooms', [RoomController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:hotels.update,hotel_owner,company_owner'])
    ->post('/rooms/{id}', [RoomController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:hotels.delete,hotel_owner,company_owner'])
    ->delete('/rooms/{id}', [RoomController::class, 'destroy']);

/*
|--------------------------------------------------------------------------
| Room Booking Actions
|--------------------------------------------------------------------------
*/
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
Route::middleware('auth:sanctum')->post('/services/requests', [ServiceController::class, 'createRequest']);

Route::prefix('services')->group(function () {
    Route::get('/', [ServiceController::class, 'index']);
    Route::post('/', [ServiceController::class, 'store']);

    Route::get('/request', [ServiceController::class, 'getAllRequests']);
    Route::get('/{serviceId}/requests', [ServiceController::class, 'getServiceRequests']);
    Route::put('/requests/{requestId}/status', [ServiceController::class, 'updateRequestStatus']);
    Route::delete('/requests/{requestId}', [ServiceController::class, 'deleteRequest']);

    Route::get('/{id}', [ServiceController::class, 'show']);
    Route::post('/{id}', [ServiceController::class, 'update']);
    Route::delete('/{id}', [ServiceController::class, 'destroy']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/getClientRequests', [ServiceController::class, 'getClientRequests']);
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
| Admin Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {

    /* ============================================================
     |  الموظفين — محتاج صلاحيات employees.*
     * ============================================================ */
    Route::middleware('permission:employees.view')->group(function () {
        Route::get('employees',      [EmployeeController::class, 'index']);
        Route::get('employees/{id}', [EmployeeController::class, 'show']);
    });

    Route::middleware('permission:employees.create')->group(function () {
        Route::post('employees', [EmployeeController::class, 'store']);
    });

    Route::middleware('permission:employees.update')->group(function () {
        Route::put('employees/{id}',               [EmployeeController::class, 'update']);
        Route::post('employees/{id}/permissions',  [EmployeeController::class, 'assignPermissions']);
        Route::delete('employees/{id}/permissions',[EmployeeController::class, 'revokeAll']);
    });

    Route::middleware('permission:employees.delete')->group(function () {
        Route::delete('employees/{id}', [EmployeeController::class, 'destroy']);
    });

    // عرض كل الصلاحيات المتاحة
    Route::get('permissions', [EmployeeController::class, 'allPermissions']);

    /* ============================================================
     |  الحظر
     * ============================================================ */
    Route::middleware('permission:blockings.view')->group(function () {
        Route::get('blockings',            [BlockingController::class, 'index']);
        Route::get('{type}/{id}/blocking', [BlockingController::class, 'show']);
    });

    Route::middleware('permission:blockings.manage')->group(function () {
        Route::post('{type}/{id}/block',   [BlockingController::class, 'block']);
        Route::post('{type}/{id}/unblock', [BlockingController::class, 'unblock']);
    });

    /* ============================================================
     |  إنشاء حجوزات من الأدمن
     * ============================================================ */
    Route::middleware('permission:bookings.create')->prefix('bookings')->group(function () {
        Route::post('room',     [AdminBookingController::class, 'createRoomBooking']);
        Route::post('property', [AdminBookingController::class, 'createPropertyBooking']);
        Route::post('offer',    [AdminBookingController::class, 'createOfferBooking']);
    });
});

/*
|--------------------------------------------------------------------------
| Companies Routes
|--------------------------------------------------------------------------
*/
Route::prefix('companies')->group(function () {
    // ✅ pending + update-status قبل {id}
    Route::middleware('auth:sanctum')->get('/pending', [CompanyController::class, 'pendingCompanies']);
    Route::middleware('auth:sanctum')->post('/{id}/update-status', [CompanyController::class, 'updateStatus']);

    Route::get('/', [CompanyController::class, 'index']);
    Route::get('/{id}', [CompanyController::class, 'show']);

    Route::middleware(['auth:sanctum', 'permission:companies.create,company_owner'])
        ->post('/', [CompanyController::class, 'store']);

    Route::middleware(['auth:sanctum', 'permission:companies.update,company_owner'])
        ->post('/{id}', [CompanyController::class, 'update']);

    Route::middleware(['auth:sanctum', 'permission:companies.delete,company_owner'])
        ->delete('/{id}', [CompanyController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Offers & Offer Bookings Routes
|--------------------------------------------------------------------------
*/
Route::prefix('offers')->group(function () {
    Route::get('/', [OfferController::class, 'index']);
    Route::get('/{id}', [OfferController::class, 'show']);

    Route::middleware(['auth:sanctum', 'permission:offers.create,hotel_owner,company_owner'])
        ->post('/', [OfferController::class, 'store']);

    Route::middleware(['auth:sanctum', 'permission:offers.update,hotel_owner,company_owner'])
        ->post('/{id}', [OfferController::class, 'update']);

    Route::middleware(['auth:sanctum', 'permission:offers.delete,hotel_owner,company_owner'])
        ->delete('/{id}', [OfferController::class, 'destroy']);
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
// ✅ pending + update-status (لازم قبل {id})
Route::middleware('auth:sanctum')->get('/properties/pending', [PropertyController::class, 'pendingProperties']);
Route::middleware('auth:sanctum')->post('/properties/{id}/update-status', [PropertyController::class, 'updateStatus']);

// القراءة العامة (المحددة الأول)
Route::get('/properties', [PropertyController::class, 'index']);
Route::get('properties/by-bookings', [PropertyController::class, 'propertiesByBookings']);
Route::get('properties/by-stars', [PropertyController::class, 'propertiesByStars']);
Route::get('properties/nearby', [PropertyController::class, 'nearbyProperties']);
Route::get('properties/{id}/bookings', [PropertyBookingController::class, 'getPropertyBookings']);
Route::get('/property-bookings/ongoing', [PropertyBookingController::class, 'ongoingBookings']);

// ⚠️ {id} في الآخر عشان ما يتعارضش مع pending/by-*/nearby
Route::get('properties/{id}', [PropertyController::class, 'show']);

// CRUD
Route::middleware(['auth:sanctum', 'permission:properties.create,property_owner'])
    ->post('/properties', [PropertyController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:properties.update,property_owner'])
    ->post('/properties/{id}', [PropertyController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:properties.delete,property_owner'])
    ->delete('/properties/{id}', [PropertyController::class, 'destroy']);

Route::prefix('property-bookings')->group(function () {
    Route::post('/', [PropertyBookingController::class, 'book']);
    Route::get('/', [PropertyBookingController::class, 'index']);
    Route::get('/{id}', [PropertyBookingController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/{id}', [PropertyBookingController::class, 'update']);
        Route::delete('/{id}', [PropertyBookingController::class, 'destroy']);
        Route::post('cancel/{id}', [PropertyBookingController::class, 'cancel']);
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
    Route::get('/', [NotificationLogController::class, 'index']);
    Route::get('/{id}', [NotificationLogController::class, 'show']);
    Route::delete('/{id}', [NotificationLogController::class, 'destroy']);
});