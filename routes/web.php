<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Storefront\AccountController;
use App\Http\Controllers\Storefront\AddressController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CertificateController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\OrdersController;
use App\Http\Controllers\Storefront\CategoryController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\BundleController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\NewsletterController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\PaytrController;
use App\Http\Controllers\Storefront\ProducerController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Controllers\Storefront\SearchController;
use App\Http\Controllers\Storefront\SitemapController;
use App\Http\Controllers\Storefront\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// E-bülten aboneliği
Route::post('/bulten', [NewsletterController::class, 'store'])->name('newsletter.store')->middleware('throttle:8,1');

// Katalog
Route::get('/kategori/{category:slug}', [CategoryController::class, 'show'])->name('category.show');
Route::get('/urun/{product:slug}', [ProductController::class, 'show'])->name('product.show');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

// Sertifikalar
Route::get('/sertifikalar', [CertificateController::class, 'index'])->name('certificates.index');

// Kutular (hazır kutular / sepetler)
Route::get('/kutular', [BundleController::class, 'index'])->name('bundles.index');
Route::get('/kutu/{bundle:slug}', [BundleController::class, 'show'])->name('bundle.show');

// Üreticiler
Route::get('/ureticiler', [ProducerController::class, 'index'])->name('producers.index');
Route::get('/uretici/{producer:slug}', [ProducerController::class, 'show'])->name('producer.show');

// SEO
Route::get('/sitemap.xml', [SitemapController::class, 'index']);
Route::get('/robots.txt', [SitemapController::class, 'robots']);

// PayTR ödeme dönüşleri (sunucu-sunucu bildirim + kullanıcı dönüşü; auth dışı)
Route::post('/odeme/paytr/notify', [PaytrController::class, 'notify'])->name('checkout.paytr.notify');
Route::match(['get', 'post'], '/odeme/paytr/sonuc', [PaytrController::class, 'return'])->name('checkout.paytr.return');

// CMS Sayfaları
Route::get('/sayfa/{slug}', [PageController::class, 'show'])->name('page.show');

// Arama
Route::get('/arama', [SearchController::class, 'index'])->name('search.index');
Route::get('/arama/oneri', [SearchController::class, 'suggest'])->name('search.suggest');

// Sepet
Route::controller(CartController::class)->prefix('sepet')->name('cart.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/ekle', 'add')->name('add');
    Route::post('/kutu-ekle', 'addBundle')->name('addBundle');
    Route::patch('/guncelle', 'update')->name('update');
    Route::delete('/cikar', 'remove')->name('remove');
    Route::post('/kupon', 'applyCoupon')->name('coupon.apply');
    Route::delete('/kupon', 'removeCoupon')->name('coupon.remove');
    Route::get('/odemeye-gec', 'checkout')->name('checkout');
});

// Favoriler
Route::controller(WishlistController::class)->prefix('favoriler')->name('wishlist.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/toggle', 'toggle')->name('toggle');
    Route::delete('/cikar', 'remove')->name('remove');
});

/*
|--------------------------------------------------------------------------
| Üyelik / Kimlik Doğrulama (müşteri)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/giris', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/giris', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/uye-ol', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/uye-ol', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/sifremi-unuttum', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/sifremi-unuttum', [PasswordResetController::class, 'sendLink'])->name('password.email')->middleware('throttle:4,1');
    Route::get('/sifre-sifirla/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/sifre-sifirla', [PasswordResetController::class, 'update'])->name('password.update');
});

Route::middleware('auth')->group(function () {
    Route::post('/cikis', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // E-posta doğrulama
    Route::get('/email/dogrula', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/dogrula/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/dogrula/gonder', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.send');

    // E-posta doğrulaması zorunlu alanlar (hesap + checkout)
    Route::middleware('verified')->group(function () {
        // Hesabım
        Route::get('/hesabim', [AccountController::class, 'index'])->name('account.index');

        // Adreslerim
        Route::get('/hesabim/adreslerim', [AddressController::class, 'index'])->name('account.addresses');
        Route::get('/hesabim/adres/ekle', [AddressController::class, 'create'])->name('account.address.create');
        Route::post('/hesabim/adres', [AddressController::class, 'store'])->name('account.address.store');
        Route::get('/hesabim/adres/{address}/duzenle', [AddressController::class, 'edit'])->name('account.address.edit');
        Route::put('/hesabim/adres/{address}', [AddressController::class, 'update'])->name('account.address.update');
        Route::delete('/hesabim/adres/{address}', [AddressController::class, 'destroy'])->name('account.address.destroy');

        // Siparişlerim
        Route::get('/hesabim/siparislerim', [OrdersController::class, 'index'])->name('account.orders');
        Route::get('/hesabim/siparis/{order}', [OrdersController::class, 'show'])->name('account.order.show');

        // Para Puanım
        Route::get('/hesabim/para-puanim', [AccountController::class, 'loyalty'])->name('account.loyalty');

        // Bilgilerim
        Route::get('/hesabim/bilgilerim', [AccountController::class, 'profile'])->name('account.profile');
        Route::put('/hesabim/bilgilerim', [AccountController::class, 'updateProfile'])->name('account.profile.update');

        // İletişim İzinleri
        Route::get('/hesabim/iletisim-izinleri', [AccountController::class, 'preferences'])->name('account.preferences');
        Route::put('/hesabim/iletisim-izinleri', [AccountController::class, 'updatePreferences'])->name('account.preferences.update');
    });
});

// Checkout / Ödeme — üyeliksiz (misafir) alışverişe açık
Route::get('/odeme', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/odeme', [CheckoutController::class, 'store'])->name('checkout.store');
Route::match(['get', 'post'], '/odeme/geri-donus/{gatewayKey}', [CheckoutController::class, 'callback'])->name('checkout.callback');
Route::get('/odeme/sonuc/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
