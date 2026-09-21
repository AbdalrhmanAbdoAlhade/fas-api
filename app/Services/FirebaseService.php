<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\NotificationLog;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private ?string $credentialsPath;
    private ?string $projectId;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->credentialsPath = env('FIREBASE_CREDENTIALS');
        $this->projectId       = env('FIREBASE_PROJECT_ID');
    }

    /**
     * الحصول على access token من Google (مع caching للجلسة)
     */
    private function getAccessToken(): ?string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        if (!file_exists($this->credentialsPath)) {
            Log::error('Firebase credentials file not found: ' . $this->credentialsPath);
            return null;
        }

        try {
            $credentials = new ServiceAccountCredentials(
                ['https://www.googleapis.com/auth/firebase.messaging'],
                $this->credentialsPath
            );

            $this->accessToken = $credentials->fetchAuthToken()['access_token'];
            return $this->accessToken;

        } catch (\Exception $e) {
            Log::error('Firebase auth error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * إرسال إشعار لجهاز واحد
     */
    public function sendToToken(
        string $deviceToken,
        string $title,
        string $body,
        array $data = [],
        ?int $userId = null
    ): bool {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            return false;
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'data' => array_merge([
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ], $data),
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)->post($url, $payload);

            // حفظ سجل الإرسال
            if ($userId) {
                NotificationLog::create([
                    'user_id'          => $userId,
                    'title'            => $title,
                    'body'             => $body,
                    'status'           => $response->successful(),
                    'firebase_response'=> json_encode($response->json()),
                ]);
            }

            if (!$response->successful()) {
                Log::warning('FCM send failed', [
                    'user_id'  => $userId,
                    'status'   => $response->status(),
                    'response' => $response->json(),
                ]);
            }

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('FCM send exception: ' . $e->getMessage(), [
                'user_id' => $userId,
            ]);
            return false;
        }
    }

    /**
     * إرسال إشعار لمستخدم محدد (عبر user_id)
     */
    public function sendToUser(
        int $userId,
        string $title,
        string $body,
        array $data = []
    ): bool {
        $deviceToken = DeviceToken::where('user_id', $userId)->value('device_token');

        if (!$deviceToken) {
            Log::info("No device token for user {$userId}");
            return false;
        }

        return $this->sendToToken($deviceToken, $title, $body, $data, $userId);
    }

    /**
     * إرسال إشعار لمجموعة مستخدمين
     */
    public function sendToUsers(
        array $userIds,
        string $title,
        string $body,
        array $data = []
    ): int {
        $successCount = 0;

        foreach ($userIds as $userId) {
            if ($this->sendToUser($userId, $title, $body, $data)) {
                $successCount++;
            }
        }

        return $successCount;
    }
}