<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Traits\Blockable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OfferController extends Controller
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
     |  INDEX
     * ============================================================ */
    public function index(Request $request)
    {
        $query = Offer::visibleTo(Auth::user())
            ->with(['hotel', 'company', 'hotels'])
            ->latest();

        if ($request->has('user_id')) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('user_id', $request->user_id);
            });
        }

        $offers = $query->get();

        return response()->json($offers);
    }

    /* ============================================================
     |  SHOW
     * ============================================================ */
    public function show($id)
    {
        $offer = Offer::visibleTo(Auth::user())
            ->with(['hotel', 'company', 'hotels'])
            ->find($id);

        if (!$offer) {
            return response()->json(['message' => __('responses.offer_not_found')], 404);
        }

        return response()->json($offer);
    }

    /* ============================================================
     |  STORE
     * ============================================================ */
    public function store(Request $request)
    {
        $user = Auth::user();

        // ✅ مسموح لأدوار الأساسية أو موظف عنده offers.create
        $hasRoleAccess = in_array($user->role, ['admin', 'hotel_owner', 'company_owner']);
        $hasPermissionAccess = $user->hasPermission('offers.create');

        if (!$hasRoleAccess && !$hasPermissionAccess) {
            return response()->json(['message' => __('responses.unauthorized_offer_creation')], 403);
        }

        $validator = Validator::make($request->all(), [
            'hotel_id'   => 'nullable|exists:hotels,id',
            'company_id' => 'nullable|exists:companies,id',

            'name'        => 'required',
            'description' => 'nullable',

            'features'           => 'nullable|string',
            'people_count'       => 'required|integer|min:1',
            'transportation'     => 'nullable|string',
            'program'            => 'nullable|string',
            'path'               => 'nullable|string',
            'required_documents' => 'nullable|string',
            'departure_time'     => 'nullable|date',
            'return_time'        => 'nullable|date|after_or_equal:departure_time',
            'price'              => 'required|numeric|min:0',

            'cover_images.*'     => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'images.*'           => 'nullable|image|mimes:jpg,jpeg,png,webp',

            'options'            => 'nullable|array',
            'options.*.name'     => 'required_with:options|string',
            'options.*.price'    => 'required_with:options|numeric|min:0',

            'hotels'             => 'nullable|array',
            'hotels.*.hotel_id'  => 'required_with:hotels|exists:hotels,id',
            'hotels.*.price'     => 'required_with:hotels|numeric|min:0',

            'discount_type'      => 'nullable|in:percentage,fixed',
            'discount_value'     => 'nullable|numeric|min:0|required_with:discount_type',
            'discount_starts_at' => 'nullable|date',
            'discount_ends_at'   => 'nullable|date|after_or_equal:discount_starts_at',

            'program_includes'             => 'nullable|array',
            'program_includes.*.key'       => 'required_with:program_includes|string',
            'program_includes.*.title'     => 'required_with:program_includes|string',
            'program_includes.*.subtitle'  => 'nullable|string',
            'program_includes.*.included'  => 'required_with:program_includes|boolean',
            'program_includes.*.price'     => 'nullable|numeric|min:0',

            'discount_enabled'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // رفع الصور
        $coverImages = [];
        if ($request->hasFile('cover_images')) {
            foreach ($request->file('cover_images') as $image) {
                $path = $image->store('offers/covers', 'public');
                $coverImages[] = '/storage/' . $path;
            }
        }

        $offerImages = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('offers/images', 'public');
                $offerImages[] = '/storage/' . $path;
            }
        }

        // إنشاء العرض
        $offer = new Offer();

        $offer->setTranslations('name', $this->normalizeTranslation($request->input('name')));

        if ($request->filled('description')) {
            $offer->setTranslations('description', $this->normalizeTranslation($request->input('description')));
        }

        $offer->fill($request->except([
            'name', 'description',
            'hotels', 'cover_images', 'images',
        ]));

        $offer->cover_images = $coverImages;
        $offer->images       = $offerImages;

        $offer->save();

        // الفنادق المتعددة
        if ($request->has('hotels') && is_array($request->hotels)) {
            $hotelsSyncData = [];
            foreach ($request->hotels as $hotelItem) {
                $hotelsSyncData[$hotelItem['hotel_id']] = ['price' => $hotelItem['price']];
            }
            $offer->hotels()->sync($hotelsSyncData);
        }

        $offer->load('hotels');

        return response()->json($offer, 201);
    }

    /* ============================================================
     |  UPDATE
     * ============================================================ */
    public function update(Request $request, $id)
    {
        $offer = Offer::find($id);

        if (!$offer) {
            return response()->json(['message' => __('responses.offer_not_found')], 404);
        }

        $user = Auth::user();

        $hasPermissionAccess = $user->hasPermission('offers.update');

        $authorized = $user->role === 'admin' ||
            ($user->role === 'company_owner' && $offer->company && $offer->company->user_id === $user->id) ||
            ($user->role === 'hotel_owner'   && $offer->hotel   && $offer->hotel->user_id   === $user->id) ||
            $hasPermissionAccess;

        if (!$authorized) {
            return response()->json(['message' => __('responses.unauthorized_offer_update')], 403);
        }

        $validator = Validator::make($request->all(), [
            'hotel_id'   => 'nullable|exists:hotels,id',
            'company_id' => 'nullable|exists:companies,id',

            'name'        => 'sometimes',
            'description' => 'nullable',

            'features'           => 'nullable|string',
            'people_count'       => 'sometimes|required|integer|min:1',
            'transportation'     => 'nullable|string',
            'program'            => 'nullable|string',
            'path'               => 'nullable|string',
            'required_documents' => 'nullable|string',
            'departure_time'     => 'nullable|date',
            'return_time'        => 'nullable|date|after_or_equal:departure_time',
            'price'              => 'sometimes|required|numeric|min:0',

            'cover_images.*'     => 'nullable|image|mimes:jpg,jpeg,png,webp',
            'images.*'           => 'nullable|image|mimes:jpg,jpeg,png,webp',

            'options'            => 'nullable|array',
            'options.*.name'     => 'required_with:options|string',
            'options.*.price'    => 'required_with:options|numeric|min:0',

            'hotels'             => 'nullable|array',
            'hotels.*.hotel_id'  => 'required_with:hotels|exists:hotels,id',
            'hotels.*.price'     => 'required_with:hotels|numeric|min:0',

            'discount_type'      => 'nullable|in:percentage,fixed',
            'discount_value'     => 'nullable|numeric|min:0|required_with:discount_type',
            'discount_starts_at' => 'nullable|date',
            'discount_ends_at'   => 'nullable|date|after_or_equal:discount_starts_at',

            'program_includes'             => 'nullable|array',
            'program_includes.*.key'       => 'required_with:program_includes|string',
            'program_includes.*.title'     => 'required_with:program_includes|string',
            'program_includes.*.subtitle'  => 'nullable|string',
            'program_includes.*.included'  => 'required_with:program_includes|boolean',
            'program_includes.*.price'     => 'nullable|numeric|min:0',

            'discount_enabled'   => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // دمج الترجمات
        if ($request->has('name')) {
            $existing = $offer->getTranslations('name');
            $incoming = $this->normalizeTranslation($request->input('name'));
            $offer->setTranslations('name', array_merge($existing, $incoming));
        }

        if ($request->has('description')) {
            $existing = $offer->getTranslations('description');
            $incoming = $this->normalizeTranslation($request->input('description'));
            $offer->setTranslations('description', array_merge($existing, $incoming));
        }

        // صور الغلاف
        $coverImages = $offer->cover_images ?? [];
        if ($request->hasFile('cover_images')) {
            if (is_array($coverImages)) {
                foreach ($coverImages as $oldImage) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $oldImage));
                }
            }

            $coverImages = [];
            foreach ($request->file('cover_images') as $image) {
                $path = $image->store('offers/covers', 'public');
                $coverImages[] = '/storage/' . $path;
            }
        }

        // صور العرض
        $offerImages = $offer->images ?? [];
        if ($request->hasFile('images')) {
            if (is_array($offerImages)) {
                foreach ($offerImages as $oldImage) {
                    Storage::disk('public')->delete(str_replace('/storage/', '', $oldImage));
                }
            }

            $offerImages = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('offers/images', 'public');
                $offerImages[] = '/storage/' . $path;
            }
        }

        $offer->fill($request->except([
            'name', 'description',
            'hotels', 'cover_images', 'images',
        ]));

        $offer->cover_images = $coverImages;
        $offer->images       = $offerImages;

        $offer->save();

        // الفنادق المتعددة
        if ($request->has('hotels')) {
            if (is_array($request->hotels) && !empty($request->hotels)) {
                $hotelsSyncData = [];
                foreach ($request->hotels as $hotelItem) {
                    $hotelsSyncData[$hotelItem['hotel_id']] = ['price' => $hotelItem['price']];
                }
                $offer->hotels()->sync($hotelsSyncData);
            } else {
                $offer->hotels()->detach();
            }
        }

        $offer->load('hotels');

        return response()->json($offer->fresh());
    }

    /* ============================================================
     |  DESTROY
     * ============================================================ */
    public function destroy($id)
    {
        $offer = Offer::find($id);

        if (!$offer) {
            return response()->json(['message' => __('responses.offer_not_found')], 404);
        }

        $user = Auth::user();

        $hasPermissionAccess = $user->hasPermission('offers.delete');

        $authorized = $user->role === 'admin' ||
            ($user->role === 'company_owner' && $offer->company && $offer->company->user_id === $user->id) ||
            ($user->role === 'hotel_owner'   && $offer->hotel   && $offer->hotel->user_id   === $user->id) ||
            $hasPermissionAccess;

        if (!$authorized) {
            return response()->json(['message' => __('responses.unauthorized_offer_delete')], 403);
        }

        // حذف الصور
        if (is_array($offer->cover_images)) {
            foreach ($offer->cover_images as $img) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $img));
            }
        }
        if (is_array($offer->images)) {
            foreach ($offer->images as $img) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $img));
            }
        }

        $offer->delete();

        return response()->json(['message' => __('responses.offer_deleted')]);
    }
}