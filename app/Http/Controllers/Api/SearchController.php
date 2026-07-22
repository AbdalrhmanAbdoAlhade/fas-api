<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hotel;
use App\Models\Room;

class SearchController extends Controller
{
public function search(Request $request)
{
    $query = $request->input('query');
    $perPage = $request->input('per_page', 10);

    // إذا لم تكن هناك كلمة بحث، رجع نتائج فارغة
    if (!$query || trim($query) === '') {
        return response()->json([
            'hotels' => [],
            'rooms' => [],
        ]);
    }

    // البحث في الفنادق
    $hotels = Hotel::with('rooms')
        ->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%$query%")
              ->orWhere('address', 'LIKE', "%$query%")
              ->orWhere('description', 'LIKE', "%$query%");
        })
        ->paginate($perPage, ['*'], 'hotels_page');

    // البحث في الغرف
    $rooms = Room::with('hotel')
        ->where(function ($q) use ($query) {
            $q->where('name', 'LIKE', "%$query%")
              ->orWhere('description', 'LIKE', "%$query%");
        })
        ->paginate($perPage, ['*'], 'rooms_page');

    return response()->json([
        'hotels' => $hotels,
        'rooms' => $rooms,
    ]);
}



public function filterHotels(Request $request)
{
    $query = Hotel::query()->with('rooms');

    // ✅ فلترة الدولة من عمود country
    if ($request->filled('country')) {
        $query->where('country', $request->country);
    }

    // ✅ فلترة السعر
    if ($request->filled('min_price') && $request->filled('max_price')) {
        $query->whereBetween('price_per_night', [$request->min_price, $request->max_price]);
    }

    // ✅ تصنيف النجوم
    if ($request->filled('stars')) {
        $query->where('stars', $request->stars);
    }

    // ✅ المرافق (facilities)
    if ($request->filled('facilities') && is_array($request->facilities)) {
        foreach ($request->facilities as $facility) {
            $query->whereJsonContains('facilities', $facility);
        }
    }

    // ✅ نوع الإقامة (لو عندك type)
    if ($request->filled('types')) {
        $query->whereIn('type', $request->types);
    }

    // ✅ ترتيب النتائج
    if ($request->filled('sort_by')) {
        switch ($request->sort_by) {
            case 'price_low':
                $query->orderBy('price_per_night');
                break;
            case 'price_high':
                $query->orderByDesc('price_per_night');
                break;
            case 'stars':
                $query->orderByDesc('stars');
                break;
            case 'latest':
                $query->latest();
                break;
        }
    }

    $hotels = $query->paginate($request->get('per_page', 10));

    return response()->json($hotels);
}



}
