<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class PropertyController extends Controller
{
    /**
     * عرض كل العقارات
     */
public function index(Request $request)
{
    $query = Property::with('user')->latest();

    // لو تم إرسال user_id، نعرض فقط ملكيات هذا المستخدم
    if ($request->has('user_id')) {
        $query->where('user_id', $request->user_id);
    }

    $properties = $query->get();

    return response()->json($properties);
}

    /**
     * عرض عقار واحد بالتفصيل
     */
    public function show($id)
    {
        $property = Property::with('user')->find($id);

        if (!$property) {
            return response()->json(['message' => __('responses.property_not_found')], 404);
        }

        return response()->json($property);
    }

    /**
     * إضافة عقار جديد
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, ['property_owner', 'admin'])) {
            return response()->json(['message' => __('responses.unauthorized_property_creation')], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
            'rooms' => 'nullable|integer|min:0',
            'beds' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'guests' => 'nullable|integer|min:0',
            'price_per_night' => 'required|numeric|min:0',
            'is_available' => 'boolean',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $validated['user_id'] = $user->id;

        // رفع الصورة الرئيسية
        if ($request->hasFile('main_image')) {
            $mainPath = $request->file('main_image')->store('properties', 'public');
            $validated['main_image'] = url('public/storage/' . $mainPath);
        }

        // رفع الصور المتعددة
        if ($request->hasFile('images')) {
            $paths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('properties', 'public');
                $paths[] = url('public/storage/' . $path);
            }
            $validated['images'] = $paths;
        }

        $property = Property::create($validated);

        return response()->json([
            'message' => __('responses.property_created'),
            'property' => $property
        ], 201);
    }

    /**
     * تحديث بيانات العقار (للأدمن أو المالك فقط)
     */
    public function update(Request $request, $id)
    {
        $property = Property::find($id);
        if (!$property) {
            return response()->json(['message' => __('responses.property_not_found')], 404);
        }

        $user = Auth::user();
        if ($property->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => __('responses.unauthorized_property_update')], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|string',
            'city' => 'sometimes|string',
            'address' => 'sometimes|string',
            'rooms' => 'nullable|integer|min:0',
            'beds' => 'nullable|integer|min:0',
            'bathrooms' => 'nullable|integer|min:0',
            'guests' => 'nullable|integer|min:0',
            'price_per_night' => 'sometimes|numeric|min:0',
            'is_available' => 'boolean',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'main_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // حذف الصورة القديمة الرئيسية عند التحديث
        if ($request->hasFile('main_image')) {
            if ($property->main_image) {
                Storage::disk('public')->delete(str_replace(url('public/storage/') . '/', '', $property->main_image));
            }
            $mainPath = $request->file('main_image')->store('properties', 'public');
            $validated['main_image'] = url('public/storage/' . $mainPath);
        }

        // حذف الصور القديمة وتحديث الجديدة
        if ($request->hasFile('images')) {
            if (is_array($property->images)) {
                foreach ($property->images as $oldImage) {
                    Storage::disk('public')->delete(str_replace(url('public/storage/') . '/', '', $oldImage));
                }
            }

            $paths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('properties', 'public');
                $paths[] = url('public/storage/' . $path);
            }
            $validated['images'] = $paths;
        }

        $property->update($validated);

        return response()->json([
            'message' => __('responses.property_updated'),
            'property' => $property
        ]);
    }
    
    /**
 * عرض العقارات حسب عدد الحجوزات (الأكثر حجزًا)
 */
public function propertiesByBookings(Request $request)
{
    $validator = Validator::make($request->all(), [
        'limit' => 'nullable|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $limit = $request->limit ?: 10;

    $properties = Property::select('properties.*', DB::raw('COUNT(bookings.id) as bookings_count'))
        ->leftJoin('bookings', 'bookings.property_id', '=', 'properties.id')
        ->groupBy('properties.id')
        ->having('bookings_count', '>', 0)
        ->orderByDesc('bookings_count')
        ->limit($limit)
        ->get();

    if ($properties->isEmpty()) {
        return response()->json(['message' => __('responses.no_properties_with_bookings')], 404);
    }

    return response()->json($properties);
}

/**
 * عرض العقارات حسب التقييم (النجوم)
 */
public function propertiesByStars(Request $request)
{
    $validator = Validator::make($request->all(), [
        'stars' => 'required|numeric|min:1|max:5',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $stars = $request->stars;

    $properties = Property::withAvg('reviews', 'stars')
        ->withCount('reviews')
        ->having('reviews_avg_stars', '>=', $stars)
        ->orderByDesc('reviews_avg_stars')
        ->get();

    if ($properties->isEmpty()) {
        return response()->json(['message' => __('responses.no_properties_with_rating')], 404);
    }

    return response()->json($properties);
}

/**
 * عرض العقارات الأقرب حسب الموقع الجغرافي
 */
public function nearbyProperties(Request $request)
{
    $validator = Validator::make($request->all(), [
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'distance_limit' => 'nullable|numeric|min:1', // مثلاً لو عايز فقط العقارات داخل 10 كم
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $latitude = $request->latitude;
    $longitude = $request->longitude;
    $distanceLimit = $request->distance_limit ?? null;

    $query = Property::select('properties.*', DB::raw("(
        6371 * acos(
            cos(radians($latitude)) * cos(radians(latitude)) *
            cos(radians(longitude) - radians($longitude)) +
            sin(radians($latitude)) * sin(radians(latitude))
        )
    ) AS distance"))
    ->orderBy('distance', 'asc');

    if ($distanceLimit) {
        $query->having('distance', '<=', $distanceLimit);
    }

    $properties = $query->get()->map(function ($property) {
        $property->distance = round($property->distance, 2); // دقة 2 رقم عشري
        return $property;
    });

    if ($properties->isEmpty()) {
        return response()->json(['message' => __('responses.no_nearby_properties')], 404);
    }

    return response()->json($properties);
}

    /**
     * حذف العقار (للأدمن أو المالك فقط)
     */
    public function destroy($id)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['message' => __('responses.property_not_found')], 404);
        }

        $user = Auth::user();
        if ($property->user_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['message' => __('responses.unauthorized_property_delete')], 403);
        }

        // حذف الصور من التخزين
        if ($property->main_image) {
            Storage::disk('public')->delete(str_replace(url('public/storage/') . '/', '', $property->main_image));
        }

        if (is_array($property->images)) {
            foreach ($property->images as $image) {
                Storage::disk('public')->delete(str_replace(url('public/storage/') . '/', '', $image));
            }
        }

        $property->delete();

        return response()->json(['message' => __('responses.deleted_successfully')]);
    }
}
