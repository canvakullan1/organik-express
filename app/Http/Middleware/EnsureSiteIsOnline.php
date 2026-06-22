<?php

namespace App\Http\Middleware;

use App\Settings\GeneralSettings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bakım modu açıkken vitrini ziyaretçilere kapatır.
 * Admin paneli ve giriş yapmış yöneticiler erişmeye devam eder.
 */
class EnsureSiteIsOnline
{
    public function handle(Request $request, Closure $next): Response
    {
        // Admin paneli her zaman erişilebilir.
        if ($request->is('admin', 'admin/*')) {
            return $next($request);
        }

        if (app(GeneralSettings::class)->maintenance_mode && ! Auth::check()) {
            return response()->view('storefront.maintenance', [], 503);
        }

        return $next($request);
    }
}
