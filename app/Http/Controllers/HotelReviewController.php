<?php

namespace App\Http\Controllers;

use App\Models\HotelReview;
use App\Models\Hotel;
use App\Models\Company;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HotelReviewController extends Controller
{
    // ✅ إضافة تقييم (لفندق / شركة / عقار)
    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:hotel,company,property',
            'id' => 'required|integer',
            'stars' => 'required|numeric|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $hotel_id = null;
        $company_id = null;
        $property_id = null;

        if ($request->type === 'hotel') {
            $hotel = Hotel::findOrFail($request->id);
            $hotel_id = $hotel->id;
        } elseif ($request->type === 'company') {
            $company = Company::findOrFail($request->id);
            $company_id = $company->id;
        } elseif ($request->type === 'property') {
            $property = Property::findOrFail($request->id);
            $property_id = $property->id;
        }

        $review = HotelReview::create([
            'hotel_id' => $hotel_id,
            'company_id' => $company_id,
            'properties_id' => $property_id,
            'user_id' => Auth::id(),
            'stars' => $request->stars,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => __('responses.review_added'),
            'review' => $review,
        ]);
    }

    // ✅ عرض التقييمات حسب النوع (فندق / شركة / عقار)
    public function index($type, $id)
    {
        if ($type === 'hotel') {
            $reviews = HotelReview::with('user')
                ->where('hotel_id', $id)
                ->get();
        } elseif ($type === 'company') {
            $reviews = HotelReview::with('user')
                ->where('company_id', $id)
                ->get();
        } elseif ($type === 'property') {
            $reviews = HotelReview::with('user')
                ->where('properties_id', $id)
                ->get();
        } else {
            return response()->json(['message' => __('responses.invalid_type')], 400);
        }

        return response()->json([
            'type' => $type,
            'reviews' => $reviews->map(function ($review) {
                return [
                    'stars' => $review->stars,
                    'comment' => $review->comment,
                    'user' => [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                        'image' => $review->user->image
                            ? url('storage/' . $review->user->image)
                            : null,
                    ],
                    'created_at' => $review->created_at->diffForHumans(),
                ];
            }),
        ]);
    }

    // ✅ حذف التقييم
    public function destroy($id)
    {
        $review = HotelReview::findOrFail($id);

        if (Auth::id() !== $review->user_id && !Auth::user()->is_admin) {
            return response()->json(['message' => __('responses.unauthorized_review_delete')], 403);
        }

        $review->delete();

        return response()->json(['message' => __('responses.review_deleted')]);
    }
}
