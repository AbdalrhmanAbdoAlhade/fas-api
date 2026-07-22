<?php

namespace App\Http\Controllers;

use App\Models\Hotel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
 use Illuminate\Support\Facades\DB;
 
 
class HotelController extends Controller
{
   
public function hotelsByBookings(Request $request)
{
    $validator = Validator::make($request->all(), [
        'limit' => 'nullable|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    $limit = $request->limit ?: 10;

    $hotels = Hotel::withCount(['rooms as bookings_count' => function ($query) {
        $query->select(DB::raw('count(*)'))
              ->join('room_bookings', 'rooms.id', '=', 'room_bookings.room_id');
    }])

    ->having('bookings_count', '>', 0)
    ->orderBy('bookings_count', 'desc')
    ->limit($limit)
    ->get();

    if ($hotels->isEmpty()) {
        return response()->json(['message' => __('responses.no_hotels_found_with_bookings')], 404);
    }

    return response()->json($hotels);
}

   
public function hotelsByStars(Request $request)
{
  
    $validator = Validator::make($request->all(), [
        'stars' => 'required|numeric|min:1|max:5',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    $stars = $request->stars;

    $hotels = Hotel::withAvg('reviews', 'stars')
        ->having('reviews_avg_stars', '>=', $stars)
        ->orderByDesc('reviews_avg_stars')
        ->get();

    if ($hotels->isEmpty()) {
        return response()->json(['message' => __('responses.hotel_not_found_with_rating')], 404);
    }

    return response()->json($hotels);
}



public function nearbyHotels(Request $request)
{
    $validator = Validator::make($request->all(), [
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    $latitude = $request->latitude;
    $longitude = $request->longitude;

    $hotels = Hotel::select('*', DB::raw("(
        6371 * acos(
            cos(radians($latitude)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians($longitude)) +
            sin(radians($latitude)) * sin(radians(latitude))
        )
    ) AS distance"))
    ->orderBy('distance', 'asc')
    ->get();

    if ($hotels->isEmpty()) {
        return response()->json(['message' => __('responses.hotel_not_found')], 404);
    }

    return response()->json($hotels);
}



    
public function index(Request $request)
{
    $query = Hotel::with('user:id,name,email,phone');

    // لو تم إرسال user_id يتم الفلترة
    if ($request->filled('user_id')) {
        $query->where('user_id', $request->user_id);
    }

    $hotels = $query->get();

    return response()->json([
        'message' => $request->filled('user_id')
            ? __('responses.user_hotels_list')
            : __('responses.all_hotels_list'),
        'hotels' => $hotels
    ]);
}



public function show($id)
{
    // جلب الفندق مع بيانات المستخدم المرتبط به
    $hotel = Hotel::with('user:id,name,email,phone')
        ->find($id);

    if (!$hotel) {
        return response()->json([
            'message' => __('responses.hotel_not_found')
        ], 404);
    }

    return response()->json([
        'message' => __('responses.hotel_details'),
        'hotel' => $hotel
    ]);
}





public function store(Request $request)
{
$user = Auth::user();

if (!in_array($user->role, ['hotel_owner','company_owner', 'admin'])) {
    return response()->json([
        'message' => __('responses.unauthorized_hotel_creation')
    ], 403);
}


    $validator = Validator::make($request->all(), [
        'name' => 'required|string',
        'images' => 'required|array',
        'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',

        'cover_images' => 'required|array',
        'cover_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',

        'stars' => 'required|numeric',
        'address' => 'required|string',
        'country' => 'required|string',
        'details' => 'required|array',
        'details.room_size' => 'required|string',
        'details.bathrooms' => 'required|string',
        'details.bedrooms' => 'required|string',
        'details.bed_type' => 'required|string',

        'description' => 'required|string',
        'facilities' => 'required|array',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'price_per_night' => 'nullable|numeric',

        'property_type_id' => 'nullable|exists:property_types,id',
        'property_type' => 'nullable|string',
        'city' => 'nullable|string',
        'area' => 'nullable|string',
        'rooms' => 'nullable|integer',

    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $coverImages = collect($request->cover_images)->map(function ($cover) {
        return '/storage/' . $cover->store('hotels/covers', 'public');
    });

    $images = collect($request->images)->map(function ($image) {
        return '/storage/' . $image->store('hotels/images', 'public');
    });

    $taxCertificate = $request->file('tax_certificate')?->store('hotels/documents', 'public');
    $ownershipDeed = $request->file('ownership_deed')?->store('hotels/documents', 'public');
    $commercialRegister = $request->file('commercial_register')?->store('hotels/documents', 'public');

    $hotel = new Hotel($request->except(['images', 'cover_images', 'tax_certificate', 'ownership_deed', 'commercial_register', 'status']));
    $hotel->images = $images;
    $hotel->cover_image = $coverImages;
    $hotel->user_id = $user->id;
    $hotel->facilities = $request->facilities;
    $hotel->details = $request->details;


    if ($taxCertificate) $hotel->tax_certificate = '/storage/' . $taxCertificate;
    if ($ownershipDeed) $hotel->ownership_deed = '/storage/' . $ownershipDeed;
    if ($commercialRegister) $hotel->commercial_register = '/storage/' . $commercialRegister;

    $hotel->save();

    return response()->json($hotel, 201);
}



public function update(Request $request, $id)
{
    $hotel = Hotel::find($id);
    if (!$hotel) {
        return response()->json(['message' => __('responses.hotel_not_found')], 404);
    }

    $user = Auth::user();
    if ($hotel->user_id != $user->id) {
        return response()->json(['message' => __('responses.unauthorized')], 403);
    }


 // 1. تحديث الصور (المصفوفات)
$imageFields = ['cover_images' => 'cover_image', 'images' => 'images'];

foreach ($imageFields as $requestKey => $dbColumn) {
    if ($request->hasFile($requestKey)) {
        // حذف الصور القديمة
        if (is_array($hotel->$dbColumn)) {
            foreach ($hotel->$dbColumn as $oldImage) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $oldImage));
            }
        }

        // الحصول على الملفات كـ Array دائماً
        $files = $request->file($requestKey);
        $filesArray = is_array($files) ? $files : [$files];

        // رفع الصور الجديدة
        $hotel->$dbColumn = array_map(function ($file) use ($dbColumn) {
            $path = $file->store('hotels/' . ($dbColumn === 'images' ? 'images' : 'covers'), 'public');
            return '/storage/' . $path;
        }, $filesArray);
    }
}

    // 2. تحديث الملفات الفردية (الوثائق)
    $docFields = ['tax_certificate', 'ownership_deed', 'commercial_register'];
    foreach ($docFields as $field) {
        if ($request->hasFile($field)) {
            if ($hotel->$field) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $hotel->$field));
            }
            $hotel->$field = '/storage/' . $request->file($field)->store('hotels/documents', 'public');
        }
    }

    // 3. تحديث البيانات البسيطة والـ JSON (باستخدام fill)
    $dataToFill = $request->except([
        'images', 'cover_images', 'tax_certificate', 'ownership_deed', 'commercial_register'
    ]);
    
    $hotel->fill($dataToFill);
    $hotel->save();

    return response()->json($hotel);
}


public function destroy($id)
{
    $hotel = Hotel::find($id);
    if (!$hotel) {
        return response()->json(['message' => __('responses.hotel_not_found')], 404);
    }

    $user = Auth::user();
    if ($hotel->user_id != $user->id) {
        return response()->json(['message' => __('responses.unauthorized')], 403);
    }

    // بما أن Laravel حولها لمصفوفة تلقائياً عبر $casts، 
    // تعامل معها كـ array مباشرة دون json_decode
    
    // حذف صور الغلاف
    if (is_array($hotel->cover_image)) {
        foreach ($hotel->cover_image as $image) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $image));
        }
    }

    // حذف الصور الأخرى
    if (is_array($hotel->images)) {
        foreach ($hotel->images as $image) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $image));
        }
    }

    // حذف باقي الملفات (هذه الحقول عادة ما تكون نصوص، لذا الكود الخاص بها صحيح)
    if ($hotel->tax_certificate) {
        Storage::disk('public')->delete(str_replace('/storage/', '', $hotel->tax_certificate));
    }

    if ($hotel->ownership_deed) {
        Storage::disk('public')->delete(str_replace('/storage/', '', $hotel->ownership_deed));
    }

    if ($hotel->commercial_register) {
        Storage::disk('public')->delete(str_replace('/storage/', '', $hotel->commercial_register));
    }

    $hotel->delete();

    return response()->json(['message' => __('responses.hotel_deleted')]);
}



}
