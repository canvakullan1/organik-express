<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Proxy'ye (LiteSpeed/cPanel) güven: X-Forwarded-Proto vb. dikkate alınsın.
        // ÖNEMLİ: yoksa istek PHP'ye 'http' olarak ulaşabilir → Livewire'ın imzalı
        // upload URL'si (hasValidSignature) 'https' ile imzalanıp 'http' ile doğrulanınca
        // 401 verir → FilePond görsel yüklemede sonsuz döner. Bu yüzden şart.
        $middleware->trustProxies(at: '*', headers:
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

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
