<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * الاستخدام:
     *   ->middleware('permission:hotels.create')
     *   ->middleware('permission:hotels.create,hotel_owner,company_owner')
     *
     * المعنى:
     *   - أول parameter = الصلاحية المطلوبة
     *   - باقي parameters = أدوار مسموح بها (اختيارية)
     *
     * القاعدة:
     *   - admin دايماً مسموح
     *   - أو عنده الصلاحية المطلوبة (للموظفين)
     *   - أو role ضمن الأدوار المسموح بها
     */
    public function handle(Request $request, Closure $next, string ...$params)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => __('responses.unauthenticated'),
            ], 401);
        }

        // ✅ الأدمن دايماً مسموح
        if ($user->role === 'admin') {
            return $next($request);
        }

        // أول parameter = الصلاحية
        $permission = $params[0] ?? null;

        // باقي parameters = أدوار مسموح بها
        $allowedRoles = array_slice($params, 1);

        // ✅ الموظف عنده الصلاحية؟
        if ($permission && $user->hasPermission($permission)) {
            return $next($request);
        }

        // ✅ دوره ضمن الأدوار المسموح بها؟
        if (!empty($allowedRoles) && in_array($user->role, $allowedRoles)) {
            return $next($request);
        }

        return response()->json([
            'status'  => false,
            'message' => __('responses.unauthorized'),
        ], 403);
    }
}