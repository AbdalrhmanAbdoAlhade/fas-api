<?php

namespace App\Http\Controllers;

use App\Models\MerchantPaymentSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MerchantPaymentSettingController extends Controller
{
  public function index()
{
    $user = Auth::user();
    
    if (! $user) {
        return response()->json(['message' => __('responses.unauthenticated')], 401);
    }

    if ($user->role === 'admin') {
        // بنختار الأعمدة اللي محتاجينها من جدول الإعدادات + بيانات اليوزر
        $merchants = MerchantPaymentSetting::with(['user' => function($query) {
                $query->select('id', 'role', 'name', 'email');
        }])
        ->latest()
        ->get();
    } else {
        $merchants = MerchantPaymentSetting::where('user_id', $user->id)
            ->with(['user' => function($query) {
                $query->select('id', 'role', 'name', 'email');
            }])
            ->latest()
            ->get();
    }

    return response()->json([
        'status' => true,
        'data' => $merchants
    ], 200);
}
    // POST /merchant-payment-settings
public function store(Request $request)
{
    $user = Auth::user();

    if (! $user) {
        return response()->json(['message' => __('responses.unauthenticated')], 401);
    }

    $rules = [
        'merchant_key' => 'required|string|max:255',
        'password' => 'required|string|max:255',
        'return_url' => 'nullable|url|max:1000',
        'user_id' => 'nullable|exists:users,id', // بس الادمن يقدر يحدد user_id
    ];

    $data = $request->validate($rules);

    // 👈 لو مش admin، الuser_id يبقى نفسه
    if ($user->role !== 'admin') {
        // منع إنشاء أكثر من إعداد لنفس المستخدم
        if (MerchantPaymentSetting::where('user_id', $user->id)->exists()) {
            return response()->json([
                'message' => __('responses.merchant_payment_settings_exist')
            ], 409);
        }

        $data['user_id'] = $user->id;
    } else {
        // admin يقدر يضيف لأي user_id
        if (isset($data['user_id']) && MerchantPaymentSetting::where('user_id', $data['user_id'])->exists()) {
            return response()->json([
                'message' => __('responses.merchant_payment_settings_user_exist')
            ], 409);
        }
        // لو admin ما كتبش user_id، نخليه هو نفسه default
        $data['user_id'] = $data['user_id'] ?? $user->id;
    }

    $merchantPaymentSetting = MerchantPaymentSetting::create($data);

    return response()->json([
        'status' => true,
        'data' => $merchantPaymentSetting
    ], 201);
}

    // GET /merchant-payment-settings/{id}
    public function show(MerchantPaymentSetting $merchantPaymentSetting)
    {
    //   dd($merchantPaymentSetting) ; 
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => __('responses.unauthenticated')], 401);
        }

        if ($user->role == 'admin' || $merchantPaymentSetting->user_id == $user->id) {
              return response()->json(['status' => true, 'data' => $merchantPaymentSetting], 200);
        }

                    return response()->json(['message' => __('responses.unauthorized')], 403);

    }

    // PUT/PATCH /merchant-payment-settings/{id}
    public function update(Request $request, MerchantPaymentSetting $merchantPaymentSetting)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => __('responses.unauthenticated')], 401);
        }

        if ($user->role !== 'admin' && $merchantPaymentSetting->user_id !== $user->id) {
            return response()->json(['message' => __('responses.unauthorized')], 403);
        }

        $rules = [
            'merchant_key' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
        ];

        $data = $request->validate($rules);

        $merchantPaymentSetting->update($data);

        return response()->json(['status' => true, 'data' => $merchantPaymentSetting], 200);
    }

    // DELETE /merchant-payment-settings/{id}
    public function destroy(MerchantPaymentSetting $merchantPaymentSetting)
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['message' => __('responses.unauthenticated')], 401);
        }

        if ($user->role !== 'admin' && $merchantPaymentSetting->user_id !== $user->id) {
            return response()->json(['message' => __('responses.unauthorized')], 403);
        }

        $merchantPaymentSetting->delete();

        return response()->json(['status' => true], 204);
    }
}
