<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Auth;
use App\Models\NotificationLog;

class FirebaseNotificationController extends Controller
{
    /**
     * ✅ حفظ توكن الجهاز للمستخدم بعد تسجيل الدخول
     */
    public function saveDeviceToken(Request $request)
    {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        $user = Auth::user();

        DeviceToken::updateOrCreate(
            ['user_id' => $user->id],
            ['device_token' => $request->device_token]
        );

        return response()->json([
            'status' => true,
            'message' => __('responses.device_token_saved'),
        ]);
    }

    /**
     * ✅ إرسال إشعار إلى مستخدم محدد عبر Firebase
     */
public function send(Request $request)
{
    $request->validate([
        'user_id' => 'required|integer',
        'title'   => 'required|string',
        'body'    => 'required|string',
    ]);

    // 🟡 جلب توكن الجهاز من قاعدة البيانات
    $deviceToken = DeviceToken::where('user_id', $request->user_id)->value('device_token');

    if (!$deviceToken) {
        return response()->json([
            'status' => false,
            'message' => __('responses.device_token_not_found'),
        ]);
    }

    // 🟡 تحميل بيانات الخدمة
    $credentialsPath = env('FIREBASE_CREDENTIALS');
    $projectId = env('FIREBASE_PROJECT_ID');

    if (!file_exists($credentialsPath)) {
        return response()->json([
            'status' => false,
            'message' => __('responses.firebase_credentials_missing'),
        ], 500);
    }

    // 🟡 إنشاء توكن وصول من Google OAuth
    $credentials = new ServiceAccountCredentials(
        ['https://www.googleapis.com/auth/firebase.messaging'],
        $credentialsPath
    );

    $accessToken = $credentials->fetchAuthToken()['access_token'];

    // 🟡 عنوان الـ API الخاص بـ FCM v1
    $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    // 🟡 إعداد البيانات للإرسال
    $payload = [
        'message' => [
            'token' => $deviceToken,
            'notification' => [
                'title' => $request->title,
                'body'  => $request->body,
            ],
            'data' => [
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
        ],
    ];

    // 🟢 إرسال الإشعار إلى Firebase
    $response = Http::withToken($accessToken)->post($url, $payload);

    // 🟢 حفظ سجل الإرسال في قاعدة البيانات
    NotificationLog::create([
        'user_id' => $request->user_id,
        'title' => $request->title,
        'body' => $request->body,
        'status' => $response->successful(),
        'firebase_response' => json_encode($response->json()),
    ]);

    // 🟢 إرجاع النتيجة
    return response()->json([
        'status' => $response->successful(),
        'response' => $response->json(),
    ]);
}

    public function markAsReceived(Request $request)
{
    $request->validate([
        'message_id' => 'required|string',
        'user_id' => 'required|integer',
    ]);

    // تخزين بيانات وصول الإشعار
    \DB::table('notification_receipts')->insert([
        'message_id' => $request->message_id,
        'user_id' => $request->user_id,
        'received_at' => now(),
    ]);

    return response()->json(['status' => true, 'message' => __('responses.notification_receipt_confirmed')]);
}

}
