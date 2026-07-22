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
        //
        $middleware->api(append: [
            SetLocale::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // إذا التوكن منتهي
        $exceptions->render(function (TokenExpiredException $e, $request) {
            return response()->json([
                'message' => __('auth.token_expired'),
                'status' => 401
            ], 401);
        });

        // إذا التوكن غير صالح
        $exceptions->render(function (TokenInvalidException $e, $request) {
            return response()->json([
                'message' => __('auth.token_invalid'),
                'status' => 401
            ], 401);
        });

        // إذا لا يوجد توكن مرسل
        $exceptions->render(function (AuthenticationException  $e, $request) {
            return response()->json([
                'message' => __('auth.token_not_provided'),
                'status' => 401
            ], 401);
        });

        // إذا لم يتم التعرف على المستخدم من التوكن
        $exceptions->render(function (UnauthorizedHttpException $e, $request) {
            return response()->json([
                'message' => __('responses.unauthorized'),
                'status' => 401
            ], 401);
        });

        // إذا لم يتم العثور على Route معينة
        $exceptions->render(function (RouteNotFoundException $e, $request) {
            return response()->json([
                'message' => __('general.route_not_found'),
                'status' => 401
            ], 401);
        });
    })
    ->create();