<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use App\Http\Middleware\SetLocale;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // ✅ مفيش redirect لـ login — رجّع 401 JSON
        $middleware->redirectGuestsTo(fn () => null);

        // Permission middleware alias
        $middleware->alias([
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);

        $middleware->api(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        // ✅ Authentication Exception → 401 JSON
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status'  => false,
                    'message' => __('responses.unauthenticated'),
                ], 401);
            }
        });

        // إذا التوكن منتهي
        $exceptions->render(function (TokenExpiredException $e, $request) {
            return response()->json([
                'message' => __('auth.token_expired'),
                'status'  => 401
            ], 401);
        });

        // إذا التوكن غير صالح
        $exceptions->render(function (TokenInvalidException $e, $request) {
            return response()->json([
                'message' => __('auth.token_invalid'),
                'status'  => 401
            ], 401);
        });

        // إذا لا يوجد توكن مرسل
        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'message' => __('auth.token_not_provided'),
                'status'  => 401
            ], 401);
        });

        // إذا لم يتم التعرف على المستخدم من التوكن
        $exceptions->render(function (UnauthorizedHttpException $e, $request) {
            return response()->json([
                'message' => __('responses.unauthorized'),
                'status'  => 401
            ], 401);
        });

        // إذا لم يتم العثور على Route معينة
        $exceptions->render(function (RouteNotFoundException $e, $request) {
            // ✅ لو الطلب API، رجّع 404 JSON مش 401
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status'  => false,
                    'message' => __('general.route_not_found', [], 'ar') ?: 'Route not found',
                ], 404);
            }
        });
    })
    ->create();