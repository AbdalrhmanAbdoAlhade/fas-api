<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Validator;




class AuthController extends Controller
{
    public function sendOtp(Request $request)
{
    Log::info('OTP request received.', ['phone' => $request->input('phone')]);

    $request->validate([
        'phone' => 'required|string|regex:/^\d{10,15}$/',
    ]);

    $phone = $request->input('phone');

    $user = User::firstOrCreate(
        ['phone' => $phone],
        [
            'name' => 'user',
            'email' => $phone . '@example.com',
            'password' => bcrypt('default_password'),
            'phone' => $phone,
            'profile_image' => 'storage/profile_images/tMqsXeb9Y2lUGZIUfQkWwX2zphvSFBE5Z2aBq7wj.png'
        ]
    );

    Log::info('User found or created.', ['user_id' => $user->id]);

      $otp = 123456;
    Cache::put('otp_' . $phone, $otp, now()->addMinutes(5));

    Log::info('OTP generated and cached.', ['phone' => $phone, 'otp' => $otp]);

    return response()->json([
        'message' => __('responses.otp_sent'),
        'otp' => $otp,
        'phone' => $phone,
    ]);
}





public function verifyOtp(Request $request)
{
    $request->validate([
        'phone' => 'required|string|regex:/^\d{10,15}$/',
        'otp' => 'required|numeric|digits:6',
    ]);

    $phone = $request->input('phone');
    $inputOtp = $request->input('otp');

    // استرجاع OTP المخزن
    $cachedOtp = Cache::get('otp_' . $phone);

    if (!$cachedOtp) {
        return response()->json([
            'message' => __('responses.otp_invalid_or_expired'),
            'status' => 'error',
        ], 400);
    }

    // التحقق من صحة الـ OTP
    if ($cachedOtp == $inputOtp) {
        // حذف الـ OTP بعد التحقق
        Cache::forget('otp_' . $phone);

        return response()->json([
            'message' => __('responses.otp_verified'),
            'status' => 'success',
        ]);
    }

    return response()->json([
        'message' => __('responses.otp_incorrect'),
        'status' => 'error',
    ], 400);
}






    // تسجيل دخول المستخدم
public function login(Request $request)
{
    Log::info('Login attempt.', ['request_data' => $request->all()]);

    if ($request->has('email') && $request->has('password')) {
        $credentials = $request->only('email', 'password');

        if (!auth()->attempt($credentials)) {
            Log::warning('Invalid email or password.', ['email' => $request->email]);
            return response()->json(['error' => __('responses.invalid_email_or_password')], 401);
        }

        $user = auth()->user();
        $token = $user->createToken('auth_token', [], now()->addWeek())->plainTextToken;

        Log::info('User logged in successfully.', ['user_id' => $user->id]);
        return response()->json([
            'token' => $token,
            'user' => $user
        ]);
    } elseif ($request->has('phone') && $request->has('otp')) {
        $request->validate([
            'phone' => 'required|string',
            'otp' => 'required|integer'
        ]);

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            Log::warning('User not found.', ['phone' => $request->phone]);
            return response()->json(['error' => __('responses.user_not_found')], 404);
        }

        $cachedOtp = Cache::get('otp_' . $user->phone);

        if ($cachedOtp != $request->otp) {
            Log::warning('Invalid or expired OTP.', ['phone' => $request->phone]);
            return response()->json(['error' => __('responses.otp_invalid_or_expired')], 401);
        }

        Cache::forget('otp_' . $user->phone);

        $token = $user->createToken('auth_token', [], now()->addWeek())->plainTextToken;

        Log::info('User logged in with OTP successfully.', ['user_id' => $user->id]);
        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    Log::error('Missing required fields in login request.');
    return response()->json(['error' => __('responses.login_missing_fields')], 400);
}





public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'national_id' => 'nullable|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'phone' => 'nullable|string|max:15|unique:users',
        'password' => 'required|string|min:8|confirmed',
        'registration_role' => 'nullable|in:Manager,technical_support',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => __('responses.registration_failed'),
            'errors' => $validator->errors(),
        ], 422);
    }

    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'phone' => $request->phone,
        'password' => Hash::make($request->password),
        'role' => 'customer',
        'registration_role' => $request->registration_role,
        'national_id' => $request->national_id,
    ]);

    $token = $user->createToken('auth_token', [], now()->addWeek())->plainTextToken;

    return response()->json([
        'message' => __('responses.customer_registered'),
        'customer' => $user,
        'token' => $token
    ]);
}

    // Update Password
public function updatePassword(Request $request)
{
    $validator = Validator::make($request->all(), [
        'phone' => 'required|string|regex:/^\d{10,15}$/',
        'otp' => 'required|numeric|digits:6',
        'new_password' => 'required|string|min:8|confirmed',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'errors' => $validator->errors()
        ], 422);
    }

    $phone = $request->input('phone');
    $inputOtp = $request->input('otp');

    $cachedOtp = Cache::get('otp_' . $phone);

    if (!$cachedOtp || $cachedOtp != $inputOtp) {
        return response()->json([
            'message' => __('responses.otp_invalid_or_expired'),
            'status' => 'error',
        ], 400);
    }

    $user = User::where('phone', $phone)->first();

    if (!$user) {
        return response()->json([
            'message' => __('responses.user_not_found'),
            'status' => 'error',
        ], 404);
    }

    $user->update(['password' => Hash::make($request->new_password)]);
    Cache::forget('otp_' . $phone);

    return response()->json(['message' => __('responses.password_updated')]);
}


public function guestLogin()
{
    // إنشاء مستخدم مؤقت
    $guest = User::create([
        'name' => 'Guest-' . Str::random(5),
        'email' => Str::uuid() . '@guest.local',
        'password' => bcrypt(Str::random(10)),
        'phone' => '0000000000', // أو أي رقم رمزي
        'role' => 'guest'
    ]);

    // إنشاء توكن لهذا الضيف
    $token = $guest->createToken('guest-token')->plainTextToken;

    return response()->json([
        'message' => __('responses.guest_login_success'),
        'guest' => $guest,
        'token' => $token
    ]);
}




public function registerHotelOwner(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'phone' => 'nullable|string|max:15|unique:users',
        'password' => 'required|string|min:8|confirmed',

        'full_name' => 'nullable|string|max:255',
        'last_name' => 'nullable|string|max:255',
        'birth_date' => 'nullable|date',
        'gender' => 'nullable|in:male,female',
        'national_id' => 'required|string|max:50',
        'national_img' => 'required|image|max:2048',
        'image' => 'nullable|image|max:2048',
        'nationality' => 'required|string|max:255',

        'tax_certificate' => 'required|file|max:4096',
        'ownership_deed' => 'required|file|max:4096',
        'commercial_register' => 'required|file|max:4096',
        'property_type' => 'required|string|max:255',
        'city' => 'nullable|string|max:255',
        'address' => 'nullable|string|max:255',
        'area' => 'nullable|string|max:255',
        'rooms' => 'nullable|integer',

        'registration_role' => 'required|in:company_owner,hotel_owner,property_owner',]);

    if ($validator->fails()) {
        return response()->json([
            'message' => __('responses.registration_failed'),
            'errors' => $validator->errors(),
        ], 422);
    }

    $data = $request->only([
        'name', 'email', 'phone', 'full_name', 'last_name',
        'birth_date', 'gender', 'national_id', 'nationality',
        'property_type', 'city', 'address', 'area', 'rooms',
         'registration_role',
    ]);

    $data['password'] = Hash::make($request->password);
    $data['role'] = 'user';
    $data['status'] = 'pending';

    if ($request->hasFile('national_img')) {
        $data['national_img'] = '/storage/' . $request->file('national_img')->store('users/national_ids', 'public');
    }

    if ($request->hasFile('image')) {
        $data['image'] = '/storage/' . $request->file('image')->store('users/images', 'public');
    }

    if ($request->hasFile('tax_certificate')) {
        $data['tax_certificate'] = '/storage/' . $request->file('tax_certificate')->store('users/docs', 'public');
    }

    if ($request->hasFile('ownership_deed')) {
        $data['ownership_deed'] = '/storage/' . $request->file('ownership_deed')->store('users/docs', 'public');
    }

    if ($request->hasFile('commercial_register')) {
        $data['commercial_register'] = '/storage/' . $request->file('commercial_register')->store('users/docs', 'public');
    }

    $user = User::create($data);

    $token = $user->createToken('auth_token')->plainTextToken;

    return response()->json([
        'message' => __('responses.user_registered'),
        'user' => $user,
        'token' => $token
    ]);
}


public function pendingUsers(Request $request)
{
    if ($request->user()->role !== 'admin') {
        return response()->json([
            'message' => __('responses.unauthorized'),
        ], 403);
    }

    $users = User::where('status', 'pending')->get();

    return response()->json([
        'message' => __('responses.pending_users_list'),
        'users' => $users,
    ]);
}



public function updateUserRole(Request $request, $userId)
{
    // التحقق من أن المستخدم الحالي هو أدمن
    if ($request->user()->role !== 'admin') {
        return response()->json(['message' => __('responses.unauthorized')], 403);
    }

    // التحقق من صحة البيانات
    $validator = Validator::make($request->all(), [
        'role' => 'required|in:user,admin,company_owner,hotel_owner,property_owner',
        'status' => 'required|boolean',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => __('responses.update_failed'),
            'errors' => $validator->errors(),
        ], 422);
    }

    // البحث عن المستخدم
    $user = User::find($userId);

    if (!$user) {
        return response()->json(['message' => __('responses.user_not_found')], 404);
    }

    // تحديث الدور والحالة
    $user->role = $request->role;
    $user->status = $request->status;
    $user->save();

    return response()->json([
        'message' => __('responses.role_status_updated'),
        'user' => $user,
    ]);
}





    // تسجيل خروج المستخدم
public function logout(Request $request)
{
    $request->user()->currentAccessToken()->delete();

    return response()->json(['message' => __('responses.logout_success')]);
}

}
