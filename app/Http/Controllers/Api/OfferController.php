<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfferController extends Controller
{
public function index(Request $request)
{
    $query = Offer::with(['hotel', 'company'])->latest();

    // لو تم إرسال user_id نعرض العروض الخاصة بشركته فقط
    if ($request->has('user_id')) {
        $query->whereHas('company', function ($q) use ($request) {
            $q->where('user_id', $request->user_id);
        });
    }

    $offers = $query->get();

    return response()->json($offers);
}

    public function store(Request $request)
    {
        $user = Auth::user();

        // السماح فقط للأدمن أو مالك فندق أو مالك شركة
        if (!in_array($user->role, ['admin', 'hotel_owner', 'company_owner'])) {
            return response()->json(['message' => __('responses.unauthorized_offer_creation')], 403);
        }

        $data = $request->validate([
            'hotel_id' => 'nullable|exists:hotels,id',
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'people_count' => 'required|integer|min:1',
            'transportation' => 'nullable|string',
            'program' => 'nullable|string',
            'path' => 'nullable|string',
            'required_documents' => 'nullable|string',
            'departure_time' => 'nullable|date',
            'return_time' => 'nullable|date|after_or_equal:departure_time',
            'price' => 'required|numeric|min:0',
            'cover_images.*' => 'nullable|image|mimes:jpg,jpeg,png',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png',
            'options' => 'nullable|array',
            'options.*.name' => 'required_with:options|string',
            'options.*.price' => 'required_with:options|numeric|min:0',
        ]);

        // رفع صور الغلاف
        $coverImages = [];
        if ($request->hasFile('cover_images')) {
            foreach ($request->file('cover_images') as $image) {
                $path = $image->store('offers/covers', 'public');
                $coverImages[] = '/storage/' . $path;
            }
        }

        // رفع صور العرض
        $offerImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('offers/images', 'public');
                $offerImages[] = '/storage/' . $path;
            }
        }

        $offer = Offer::create([
            ...$data,
            'cover_images' => $coverImages,
            'images' => $offerImages,
        ]);

        return response()->json($offer, 201);
    }

    public function show($id)
    {
        $offer = Offer::with(['hotel', 'company'])->findOrFail($id);
        return response()->json($offer);
    }

    public function update(Request $request, $id)
    {
        $offer = Offer::findOrFail($id);
        $user = Auth::user();

        // التحقق من الصلاحية: admin أو صاحب الشركة أو صاحب الفندق
        $authorized = $user->role === 'admin' ||
            ($user->role === 'company_owner' && $offer->company && $offer->company->user_id === $user->id) ||
            ($user->role === 'hotel_owner' && $offer->hotel && $offer->hotel->user_id === $user->id);

        if (!$authorized) {
            return response()->json(['message' => __('responses.unauthorized_offer_update')], 403);
        }

        $data = $request->validate([
            'hotel_id' => 'nullable|exists:hotels,id',
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'sometimes|required|string',
            'description' => 'nullable|string',
            'features' => 'nullable|string',
            'people_count' => 'sometimes|required|integer|min:1',
            'transportation' => 'nullable|string',
            'program' => 'nullable|string',
            'path' => 'nullable|string',
            'required_documents' => 'nullable|string',
            'departure_time' => 'nullable|date',
            'return_time' => 'nullable|date|after_or_equal:departure_time',
            'price' => 'sometimes|required|numeric|min:0',
            'cover_images.*' => 'nullable|image|mimes:jpg,jpeg,png',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png',
            'options' => 'nullable|array',
            'options.*.name' => 'required_with:options|string',
            'options.*.price' => 'required_with:options|numeric|min:0',
        ]);

        // تحديث صور الغلاف
        $coverImages = $offer->cover_images ?? [];
        if ($request->hasFile('cover_images')) {
            $coverImages = [];
            foreach ($request->file('cover_images') as $image) {
                $path = $image->store('offers/covers', 'public');
                $coverImages[] = '/storage/' . $path;
            }
        }

        // تحديث صور العرض
        $offerImages = $offer->images ?? [];
        if ($request->hasFile('images')) {
            $offerImages = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('offers/images', 'public');
                $offerImages[] = '/storage/' . $path;
            }
        }

        $data['cover_images'] = $coverImages;
        $data['images'] = $offerImages;

        $offer->update($data);

        return response()->json($offer);
    }

    public function destroy($id)
    {
        $offer = Offer::findOrFail($id);
        $user = Auth::user();

        // التحقق من الصلاحية: admin أو صاحب الشركة أو صاحب الفندق
        $authorized = $user->role === 'admin' ||
            ($user->role === 'company_owner' && $offer->company && $offer->company->user_id === $user->id) ||
            ($user->role === 'hotel_owner' && $offer->hotel && $offer->hotel->user_id === $user->id);

        if (!$authorized) {
            return response()->json(['message' => __('responses.unauthorized_offer_delete')], 403);
        }

        $offer->delete();

        return response()->json(['message' => __('responses.offer_deleted')]);
    }
}
