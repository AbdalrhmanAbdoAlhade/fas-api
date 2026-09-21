<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Support\LocaleResolver;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        $locale = LocaleResolver::resolve(
            $request->header('Accept-Language'),
            $user->locale ?? null,
            $user->phone ?? $request->input('phone')
        );

        if ($user && $user->locale !== $locale) {
            $user->update(['locale' => $locale]);
        }

        App::setLocale($locale);

        return $next($request);
    }
}