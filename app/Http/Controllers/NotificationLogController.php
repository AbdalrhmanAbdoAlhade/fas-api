<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use Illuminate\Http\Request;

class NotificationLogController extends Controller
{
    /**
     * عرض كل الإشعارات المرسلة
     */
    public function index()
    {
        $logs = NotificationLog::with('user:id,name,email')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $logs
        ]);
    }

    /**
     * عرض إشعار واحد بالتفصيل
     */
    public function show($id)
    {
        $log = NotificationLog::with('user:id,name,email')->find($id);

        if (!$log) {
            return response()->json(['status' => false, 'message' => __('responses.notification_log_show_not_found')], 404);
        }

        return response()->json(['status' => true, 'data' => $log]);
    }

    /**
     * حذف سجل إشعار
     */
    public function destroy($id)
    {
        $log = NotificationLog::find($id);

        if (!$log) {
            return response()->json(['status' => false, 'message' => __('responses.notification_log_show_not_found')], 404);
        }

        $log->delete();

        return response()->json(['status' => true, 'message' => __('responses.notification_deleted')]);
    }
}
