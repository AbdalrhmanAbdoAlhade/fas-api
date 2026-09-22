<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    /**
     * helper: توحيد شكل الحقل المترجم
     */
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
     |  STORE
     * ============================================================ */
   public function store(Request $request)
{
    $user = Auth::user();

    $hasRoleAccess = in_array($user->role, ['hotel_owner', 'company_owner', 'admin']);
    $hasPermissionAccess = $user->hasPermission('hotels.create');

    if (!$hasRoleAccess && !$hasPermissionAccess) {
        return response()->json(['message' => __('responses.unauthorized')], 403);
    }

    $validator = Validator::make($request->all(), [
        'hotel_id'        => 'required|exists:hotels,id',
        'name'            => 'required',
        'type'            => 'nullable|string|max:50',          // single, double, suite...
        'cover_image'     => 'required|image|max:20048',
        'images.*'        => 'image|max:20048',
        'details'         => 'nullable',
        'size'            => 'nullable|string',
        'facilities'      => 'nullable',
        'description'     => 'nullable',
        'floor_number'    => 'nullable|string',
        'room_number'     => 'nullable|string',
        'price_per_night' => 'required|numeric',
        'quantity'        => 'required|integer|min:1',
        'max_occupancy'   => 'nullable|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    $coverImagePath = $request->file('cover_image')->store('rooms/covers', 'public');
    $coverImagePath = '/' . $coverImagePath;

    $images = [];
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $path = $image->store('rooms/images', 'public');
            $images[] = '/' . $path;
        }
    }

    $room = new Room();

    $room->setTranslations('name', $this->normalizeTranslation($request->input('name')));

    if ($request->filled('description')) {
        $room->setTranslations('description', $this->normalizeTranslation($request->input('description')));
    }
    if ($request->filled('details')) {
        $room->setTranslations('details', $this->normalizeTranslation($request->input('details')));
    }
    if ($request->filled('facilities')) {
        $room->setTranslations('facilities', $this->normalizeTranslation($request->input('facilities')));
    }

    $room->hotel_id        = $request->hotel_id;
    $room->type            = $request->type;
    $room->cover_image     = $coverImagePath;
    $room->images          = $images;
    $room->size            = $request->size;
    $room->room_number     = $request->room_number;
    $room->floor_number    = $request->floor_number;
    $room->price_per_night = $request->price_per_night;
    $room->quantity        = $request->quantity;
    $room->max_occupancy   = $request->max_occupancy;

    $room->save();

    return response()->json($room, 201);
}

    /* ============================================================
     |  INDEX
     * ============================================================ */
    public function index()
    {
        $rooms = Room::visibleTo(Auth::user())
            ->with('hotel')
            ->get();

        foreach ($rooms as $room) {
            if (
                $room->hotel &&
                is_array($room->hotel->cover_image) &&
                !empty($room->hotel->cover_image)
            ) {
                foreach ($room->hotel->cover_image as $key => $image) {
                    if (!str_starts_with($image, '/storage/')) {
                        $room->hotel->cover_image[$key] = '/storage/' . $image;
                    }
                }
            }
        }

        return response()->json($rooms);
    }

    /* ============================================================
     |  SHOW
     * ============================================================ */
    public function show($id)
    {
        $room = Room::visibleTo(Auth::user())
            ->with('hotel')
            ->find($id);

        if (!$room) {
            return response()->json(['error' => __('responses.room_not_found')], 404);
        }

        return response()->json($room);
    }

    /* ============================================================
     |  UPDATE
     * ============================================================ */
  public function update(Request $request, $id)
{
    $room = Room::find($id);

    if (!$room) {
        return response()->json(['error' => __('responses.room_not_found')], 404);
    }

    $user = Auth::user();
    $hotelOwnerId = $room->hotel?->user_id;
    $isOwner = ($hotelOwnerId === $user->id);
    $hasPermissionAccess = $user->hasPermission('hotels.update');

    if (!$isOwner && $user->role !== 'admin' && !$hasPermissionAccess) {
        return response()->json(['message' => __('responses.unauthorized')], 403);
    }

    $validator = Validator::make($request->all(), [
        'name'            => 'sometimes',
        'type'            => 'nullable|string|max:50',
        'cover_image'     => 'nullable|image|max:20048',
        'images.*'        => 'image|max:20048',
        'details'         => 'nullable',
        'size'            => 'nullable|string',
        'facilities'      => 'nullable',
        'description'     => 'nullable',
        'price_per_night' => 'sometimes|required|numeric',
        'floor_number'    => 'nullable|string',
        'room_number'     => 'nullable|string',
        'quantity'        => 'sometimes|integer|min:1',
        'max_occupancy'   => 'nullable|integer|min:1',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // الترجمات
    if ($request->has('name')) {
        $existing = $room->getTranslations('name');
        $incoming = $this->normalizeTranslation($request->input('name'));
        $room->setTranslations('name', array_merge($existing, $incoming));
    }
    if ($request->has('description')) {
        $existing = $room->getTranslations('description');
        $incoming = $this->normalizeTranslation($request->input('description'));
        $room->setTranslations('description', array_merge($existing, $incoming));
    }
    if ($request->has('details')) {
        $existing = $room->getTranslations('details');
        $incoming = $this->normalizeTranslation($request->input('details'));
        $room->setTranslations('details', array_merge($existing, $incoming));
    }
    if ($request->has('facilities')) {
        $existing = $room->getTranslations('facilities');
        $incoming = $this->normalizeTranslation($request->input('facilities'));
        $room->setTranslations('facilities', array_merge($existing, $incoming));
    }

    // الصور
    if ($request->hasFile('cover_image')) {
        if ($room->cover_image) {
            Storage::disk('public')->delete(ltrim($room->cover_image, '/'));
        }
        $coverImagePath = $request->file('cover_image')->store('rooms/covers', 'public');
        $room->cover_image = '/' . $coverImagePath;
    }

    if ($request->hasFile('images')) {
        if (is_array($room->images)) {
            foreach ($room->images as $oldImage) {
                Storage::disk('public')->delete(ltrim($oldImage, '/'));
            }
        }
        $images = [];
        foreach ($request->file('images') as $image) {
            $path = $image->store('rooms/images', 'public');
            $images[] = '/' . $path;
        }
        $room->images = $images;
    }

    // باقي الحقول
    $room->fill($request->only([
        'type', 'size', 'room_number', 'floor_number',
        'price_per_night', 'quantity', 'max_occupancy',
    ]));

    $room->save();

    return response()->json($room->fresh()->load('hotel'));
}

    /* ============================================================
     |  DESTROY
     * ============================================================ */
    public function destroy($id)
    {
        $room = Room::find($id);

        if (!$room) {
            return response()->json(['error' => __('responses.room_not_found')], 404);
        }

        $user = Auth::user();

        // ✅ مسموح لصاحب الفندق / شركة / أدمن / موظف عنده صلاحية hotels.delete
        $hotelOwnerId = $room->hotel?->user_id;
        $isOwner = ($hotelOwnerId === $user->id);
        $hasPermissionAccess = $user->hasPermission('hotels.delete');

        if (!$isOwner && $user->role !== 'admin' && !$hasPermissionAccess) {
            return response()->json(['message' => __('responses.unauthorized')], 403);
        }

        // حذف الصور
        if ($room->cover_image) {
            Storage::disk('public')->delete(ltrim($room->cover_image, '/'));
        }
        if (is_array($room->images)) {
            foreach ($room->images as $img) {
                Storage::disk('public')->delete(ltrim($img, '/'));
            }
        }

        $room->delete();

        return response()->json(['message' => __('responses.room_deleted')]);
    }

    /* ============================================================
     |  Rooms by Hotel
     * ============================================================ */
  public function getRoomsByHotel($hotel_id)
{
    $rooms = Room::visibleTo(Auth::user())
        ->where('hotel_id', $hotel_id)
        ->with('hotel')
        ->get();

    if ($rooms->isEmpty()) {
        return response()->json(['message' => __('responses.no_rooms_for_hotel')], 404);
    }

    $startDate = request('start_date');
    $endDate   = request('end_date');

    $rooms->each(function ($room) use ($startDate, $endDate) {
        $booked = 0;

        if ($startDate && $endDate) {
            $booked = \App\Models\RoomBooking::where('room_id', $room->id)
                ->where('status', '!=', 'cancelled')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q2) use ($startDate, $endDate) {
                          $q2->where('start_date', '<=', $startDate)
                             ->where('end_date', '>=', $endDate);
                      });
                })
                ->sum('number_of_rooms');
        }

        $room->available = max(0, $room->quantity - $booked);
    });

    return response()->json([
        'status'   => 'success',
        'hotel_id' => $hotel_id,
        'rooms'    => $rooms,
    ], 200);
}
}