<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'hotel_id' => 'required|exists:hotels,id',
        'name' => 'required|string',
       'cover_image' => 'required|image|max:20048',
        'images.*'    => 'image|max:20048',
        'details' => 'nullable|string',
        'size' => 'nullable|string',
        'facilities' => 'nullable|string',
        'description' => 'nullable|string',
        'floor_number' => 'nullable|string',
        'room_number' => 'nullable|string',
        'price_per_night' => 'required|numeric',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // حفظ صورة الغلاف في المسار النسبي فقط
    $coverImagePath = $request->file('cover_image')->store('rooms/covers', 'public');
    $coverImagePath = '/' . $coverImagePath;  // إضافة /storage/ إلى المسار

    // حفظ الصور الأخرى في المسار النسبي فقط
    $images = [];
    if ($request->hasFile('images')) {
        foreach ($request->file('images') as $image) {
            $path = $image->store('rooms/images', 'public');
            $images[] = '/' . $path;  // إضافة /storage/ إلى المسار
        }
    }

    // إنشاء الغرفة
    $room = Room::create([
        'hotel_id' => $request->hotel_id,
        'name' => $request->name,
        'cover_image' => $coverImagePath, 
        'images' => $images,              
        'details' => $request->details,
        'size' => $request->size,
        'room_number' => $request->room_number,
        'floor_number' => $request->floor_number,
        'facilities' => $request->facilities,
        'description' => $request->description,
        'price_per_night' => $request->price_per_night,
        
    ]);

    return response()->json($room, 201);
}


public function index()
{
    $rooms = Room::with('hotel')->get();

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


    public function show($id)
    {
        $room = Room::with('hotel')->find($id);

        if (!$room) {
            return response()->json(['error' => __('responses.room_not_found')], 404);
        }

        return response()->json($room);
    }

public function update(Request $request, $id)
{
    $room = Room::find($id);

    if (!$room) {
        return response()->json(['error' => __('responses.room_not_found')], 404);
    }

    $validator = Validator::make($request->all(), [
        'name' => 'sometimes|required|string',
'cover_image' => 'nullable|image|max:20048',
'images.*'    => 'image|max:20048',
        'details' => 'nullable|string',
        'size' => 'nullable|string',
        'facilities' => 'nullable|string',
        'description' => 'nullable|string',
        'price_per_night' => 'sometimes|required|numeric',
        'floor_number' => 'nullable|string',
        'room_number' => 'nullable|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    // تحديث صورة الكفر لو موجودة
    if ($request->hasFile('cover_image')) {
        $coverImagePath = $request->file('cover_image')->store('rooms/covers', 'public');
        $room->cover_image = $coverImagePath;
    }

    // تحديث الصور الإضافية لو موجودة
    if ($request->hasFile('images')) {
        $images = [];
        foreach ($request->file('images') as $image) {
            $path = $image->store('rooms/images', 'public');
            $images[] = $path;
        }
        $room->images = $images;
    }

    // تحديث باقي الحقول
    $room->fill($request->only([
        'name', 'details', 'size', 'room_number', 'floor_number', 'facilities', 'description', 'price_per_night'
    ]));

    $room->save();

    return response()->json($room);
}


public function destroy($id)
{
    $room = Room::find($id);

    if (!$room) {
        return response()->json(['error' => __('responses.room_not_found')], 404);
    }

    $room->delete();

    return response()->json(['message' => __('responses.room_deleted')]);
}

public function getRoomsByHotel($hotel_id)
{
    // التحقق من وجود الفندق
    $rooms = Room::where('hotel_id', $hotel_id)
                ->with('hotel')
                ->get();

    if ($rooms->isEmpty()) {
        return response()->json(['message' => __('responses.no_rooms_for_hotel')], 404);
    }

    return response()->json([
        'status' => 'success',
        'hotel_id' => $hotel_id,
        'rooms' => $rooms
    ], 200);
}


}
