<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Bakım modu kontrolü (admin paneli hariç) tüm web isteklerinde.
        $middleware->web(append: [
            \App\Http\Middleware\EnsureSiteIsOnline::class,
            \App\Http\Middleware\TrackVisit::class,
        ]);

        // Kimlik doğrulama yönlendirmeleri (müşteri vitrini)
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo('/hesabim');

        // Ödeme sağlayıcılarının dış domain'den yaptığı POST'lar CSRF dışı tutulur.
        $middleware->validateCsrfTokens(except: [
            'odeme/geri-donus/*',   // iyzico 3DS callback
            'odeme/paytr/notify',   // PayTR bildirim (sunucu-sunucu)
            'odeme/paytr/sonuc',    // PayTR kullanıcı dönüşü
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
