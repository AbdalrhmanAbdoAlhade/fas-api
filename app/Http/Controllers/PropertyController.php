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
    private function normalizeTranslation($value): array
    {
        if (is_array($value)) {
            return array_filter([
                'ar' => $value['ar'] ?? null,
                'en' => $value['en'] ?? null,
            ]);
        }

        return [app()->getLocale() => $value];
    }

    /* ============================================================
     |  INDEX
     * ============================================================ */
    public function index(Request $request)
    {
        $query = Property::visibleTo(Auth::user())
            ->with('user')
            ->latest();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        return response()->json($query->get());
    }

    /* ============================================================
     |  SHOW
     * ============================================================ */
    public function show($id)
    {
        $property = Property::visibleTo(Auth::user())
            ->with('user')
            ->find($id);

        if (!$property) {
            return response()->json(['message' => __('responses.property_not_found')], 404);
        }

        return response()->json($property);
    }

    /* ============================================================
     |  STORE
     * ============================================================ */
    public function store(Request $request)
    {
        $user = Auth::user();

        $hasRoleAccess = in_array($user->role, ['property_owner', 'admin']);
        $hasPermissionAccess = $user->hasPermission('properties.create');

        if (!$hasRoleAccess && !$hasPermissionAccess) {
            return response()->json(['message' => __('responses.unauthorized_property_creation')], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'           => 'required',
            'description'     => 'nullable',
            'type'            => 'required',
            'city'            => 'required',
            'address'         => 'required',
            'country'         => 'nullable|string|max:255',
            'area'            => 'nullable|string|max:255',
            'rooms'           => 'nullable|integer|min:0',
            'beds'            => 'nullable|integer|min:0',
            'bathrooms'       => 'nullable|integer|min:0',
            'guests'          => 'nullable|integer|min:0',
            'price_per_night' => 'required|numeric|min:0',
            'is_available'    => 'nullable|boolean',
            'latitude'        => 'required|numeric',
            'longitude'       => 'required|numeric',
            'main_image'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'images.*'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            'national_id'         => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'phone'               => 'nullable|string|max:30',
            'ownership_deed'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'commercial_register' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'tax_certificate'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $property = new Property();

        // الترجمات
        $property->setTranslations('title', $this->normalizeTranslation($request->input('title')));

        if ($request->filled('description')) {
            $property->setTranslations('description', $this->normalizeTranslation($request->input('description')));
        }

        $property->setTranslations('type', $this->normalizeTranslation($request->input('type')));
        $property->setTranslations('city', $this->normalizeTranslation($request->input('city')));
        $property->setTranslations('address', $this->normalizeTranslation($request->input('address')));

        // استثناء الحقول الملفات + المترجمة (country و area نص عادي — هيتحفظوا تلقائيًا)
        $property->fill($request->except([
            'title', 'description', 'type', 'city', 'address',
            'main_image', 'images',
            'national_id', 'ownership_deed', 'commercial_register', 'tax_certificate',
        ]));

        $property->user_id = $user->id;
        $property->status  = 'pending';   // ← الحالة الابتدائية

        if ($request->hasFile('main_image')) {
            $mainPath = $request->file('main_image')->store('properties', 'public');
            $property->main_image = url('public/storage/' . $mainPath);
        }

        if ($request->hasFile('images')) {
            $paths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('properties', 'public');
                $paths[] = url('public/storage/' . $path);
            }
            $property->images = $paths;
        }

        $docFields = ['national_id', 'ownership_deed', 'commercial_register', 'tax_certificate'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                $path = $request->file($field)->store('properties/documents', 'public');
                $property->$field = '/storage/' . $path;
            }
        }

        $property->save();

        return response()->json([
            'message'  => __('responses.property_created'),
            'property' => $property,
        ], 201);
    }

    /* ============================================================
     |  UPDATE
     * ============================================================ */
    public function update(Request $request, $id)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['message' => __('responses.property_not_found')], 404);
        }

        $user = Auth::user();

        $isOwner = $property->user_id === $user->id;
        $hasPermissionAccess = $user->hasPermission('properties.update');

        if (!$isOwner && $user->role !== 'admin' && !$hasPermissionAccess) {
            return response()->json(['message' => __('responses.unauthorized_property_update')], 403);
        }

        $validator = Validator::make($request->all(), [
            'title'           => 'sometimes',
            'description'     => 'nullable',
            'type'            => 'sometimes',
            'city'            => 'sometimes',
            'address'         => 'sometimes',
            'country'         => 'nullable|string|max:255',
            'area'            => 'nullable|string|max:255',
            'rooms'           => 'nullable|integer|min:0',
            'beds'            => 'nullable|integer|min:0',
            'bathrooms'       => 'nullable|integer|min:0',
            'guests'          => 'nullable|integer|min:0',
            'price_per_night' => 'sometimes|numeric|min:0',
            'is_available'    => 'nullable|boolean',
            'latitude'        => 'nullable|numeric',
            'longitude'       => 'nullable|numeric',
            'main_image'      => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'images.*'        => 'nullable|image|mimes:jpeg,png,jpg|max:2048',

            'national_id'         => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'phone'               => 'nullable|string|max:30',
            'ownership_deed'      => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'commercial_register' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'tax_certificate'     => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // دمج الترجمات
        if ($request->has('title')) {
            $existing = $property->getTranslations('title');
            $incoming = $this->normalizeTranslation($request->input('title'));
            $property->setTranslations('title', array_merge($existing, $incoming));
        }

        if ($request->has('description')) {
            $existing = $property->getTranslations('description');
            $incoming = $this->normalizeTranslation($request->input('description'));
            $property->setTranslations('description', array_merge($existing, $incoming));
        }

        if ($request->has('type')) {
            $existing = $property->getTranslations('type');
            $incoming = $this->normalizeTranslation($request->input('type'));
            $property->setTranslations('type', array_merge($existing, $incoming));
        }

        if ($request->has('city')) {
            $existing = $property->getTranslations('city');
            $incoming = $this->normalizeTranslation($request->input('city'));
            $property->setTranslations('city', array_merge($existing, $incoming));
        }

        if ($request->has('address')) {
            $existing = $property->getTranslations('address');
            $incoming = $this->normalizeTranslation($request->input('address'));
            $property->setTranslations('address', array_merge($existing, $incoming));
        }

        // الصورة الرئيسية
        if ($request->hasFile('main_image')) {
            if ($property->main_image) {
                Storage::disk('public')->delete(str_replace(url('public/storage/') . '/', '', $property->main_image));
            }
            $mainPath = $request->file('main_image')->store('properties', 'public');
            $property->main_image = url('public/storage/' . $mainPath);
        }

        // الصور الإضافية
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
            $property->images = $paths;
        }

        // الوثائق
        $docFields = ['national_id', 'ownership_deed', 'commercial_register', 'tax_certificate'];
        foreach ($docFields as $field) {
            if ($request->hasFile($field)) {
                if ($property->$field) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $property->$field));
                }
                $path = $request->file($field)->store('properties/documents', 'public');
                $property->$field = '/storage/' . $path;
            }
        }

        // استثناء الملفات + المترجمة (country و area نص عادي — هيتحفظوا تلقائيًا)
        $property->fill($request->except([
            'title', 'description', 'type', 'city', 'address',
            'main_image', 'images',
            'national_id', 'ownership_deed', 'commercial_register', 'tax_certificate',
        ]));

        $property->save();

        return response()->json([
            'message'  => __('responses.property_updated'),
            'property' => $property->fresh(),
        ]);
    }

    /* ============================================================
     |  PENDING PROPERTIES (للأدمن)
     * ============================================================ */
    public function pendingProperties(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'status'  => false,
                'message' => __('responses.unauthorized'),
            ], 403);
        }

        $properties = Property::where('status', 'pending')
            ->with('user:id,name,email,phone,status,registration_role')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'total'  => $properties->count(),
            'data'   => $properties,
        ]);
    }

    /* ============================================================
     |  UPDATE STATUS (للأدمن)
     * ============================================================ */
    public function updateStatus(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->role !== 'admin') {
            return response()->json([
                'status'  => false,
                'message' => __('responses.unauthorized'),
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $property = Property::find($id);

        if (!$property) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.property_not_found'),
            ], 404);
        }

        $newStatus = $request->input('status');
        $property->update(['status' => $newStatus]);

        // ✅ تحويل role و status الـ user (لو اتقبل)
        if ($newStatus === 'approved' && $property->user) {
            if (in_array($property->user->role, ['user', 'employee'])) {
                $property->user->update([
                    'role'   => 'property_owner',
                    'status' => 'active',
                ]);
            }
        }

        return response()->json([
            'status'  => true,
            'message' => $newStatus === 'approved'
                ? 'تم قبول العقار بنجاح.'
                : 'تم رفض العقار.',
            'data'    => $property->fresh()->load('user:id,name,email,role,status'),
        ]);
    }

    /* ============================================================
     |  FILTERS
     * ============================================================ */
    public function propertiesByBookings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $limit = $request->limit ?: 10;

        $properties = Property::visibleTo(Auth::user())
            ->select('properties.*', DB::raw('COUNT(bookings.id) as bookings_count'))
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

    public function propertiesByStars(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'stars' => 'required|numeric|min:1|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $stars = $request->stars;

        $properties = Property::visibleTo(Auth::user())
            ->withAvg('reviews', 'stars')
            ->withCount('reviews')
            ->having('reviews_avg_stars', '>=', $stars)
            ->orderByDesc('reviews_avg_stars')
            ->get();

        if ($properties->isEmpty()) {
            return response()->json(['message' => __('responses.no_properties_with_rating')], 404);
        }

        return response()->json($properties);
    }

    public function nearbyProperties(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude'       => 'required|numeric',
            'longitude'      => 'required|numeric',
            'distance_limit' => 'nullable|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $latitude      = $request->latitude;
        $longitude     = $request->longitude;
        $distanceLimit = $request->distance_limit ?? null;

        $query = Property::visibleTo(Auth::user())
            ->select('properties.*', DB::raw("(
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
            $property->distance = round($property->distance, 2);
            return $property;
        });

        if ($properties->isEmpty()) {
            return response()->json(['message' => __('responses.no_nearby_properties')], 404);
        }

        return response()->json($properties);
    }

    /* ============================================================
     |  DESTROY
     * ============================================================ */
    public function destroy($id)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['message' => __('responses.property_not_found')], 404);
        }

        $user = Auth::user();

        $isOwner = $property->user_id === $user->id;
        $hasPermissionAccess = $user->hasPermission('properties.delete');

        if (!$isOwner && $user->role !== 'admin' && !$hasPermissionAccess) {
            return response()->json(['message' => __('responses.unauthorized_property_delete')], 403);
        }

        if ($property->main_image) {
            Storage::disk('public')->delete(str_replace(url('public/storage/') . '/', '', $property->main_image));
        }

        if (is_array($property->images)) {
            foreach ($property->images as $image) {
                Storage::disk('public')->delete(str_replace(url('public/storage/') . '/', '', $image));
            }
        }

        foreach (['national_id', 'ownership_deed', 'commercial_register', 'tax_certificate'] as $field) {
            if ($property->$field) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $property->$field));
            }
        }

        $property->delete();

        return response()->json(['message' => __('responses.deleted_successfully')]);
    }
}