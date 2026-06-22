<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use App\Services\Cart\CartService;
use App\Services\Wishlist\WishlistService;
use App\Settings\ContactSettings;
use App\Settings\GeneralSettings;
use App\Settings\MailSettings;
use App\Settings\SeoSettings;
use App\Settings\SocialSettings;
use App\Settings\ThemeSettings;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CartService::class);
        $this->app->scoped(WishlistService::class);
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        $this->applyMailSettings();
        $this->localizeAuthEmails();

        // Vitrin layout'una mega menü, sepet/favori sayaçları ve TÜM site ayarlarını paylaş.
        View::composer('layouts.storefront', function ($view) {
            $view->with('menuCategories', Category::query()
                ->active()
                ->roots()
                ->where('show_in_menu', true)
                ->with(['children' => fn ($q) => $q->active()->where('show_in_menu', true)])
                ->orderBy('sort_order')
                ->get());

            $view->with('cartCount', app(CartService::class)->count());
            $view->with('wishlistCount', app(WishlistService::class)->count());

            // Site ayarları
            $view->with('general', app(GeneralSettings::class));
            $view->with('theme', app(ThemeSettings::class));
            $view->with('contact', app(ContactSettings::class));
            $view->with('social', app(SocialSettings::class));
            $view->with('seo', app(SeoSettings::class));

            // Footer'da gösterilecek sayfalar (gruplu)
            $view->with('footerPages', Page::published()
                ->where('show_in_footer', true)
                ->orderBy('sort_order')
                ->get()
                ->groupBy('footer_group'));

            // Özel header menüsü (tanımlıysa kategorilerin yerine geçer)
            $view->with('headerMenu', MenuItem::active()
                ->where('location', 'header')
                ->whereNull('parent_id')
                ->with('children')
                ->orderBy('sort_order')
                ->get());

            // Footer menüsü (parent = sütun, children = linkler)
            $view->with('footerMenu', MenuItem::active()
                ->where('location', 'footer')
                ->whereNull('parent_id')
                ->with('children')
                ->orderBy('sort_order')
                ->get());
        });
    }

    /** Admin'den girilen mail (SMTP) ayarlarını çalışma zamanı config'e uygular. */
    private function applyMailSettings(): void
    {
        // Konsol (migrate/seed) sırasında ayar tablosu henüz hazır olmayabilir → güvenli geç.
        try {
            $m = app(MailSettings::class);

            config([
                'mail.default' => $m->mailer ?: 'log',
                'mail.from.address' => $m->from_address ?: config('mail.from.address'),
                'mail.from.name' => $m->from_name ?: config('mail.from.name'),
            ]);

            if ($m->mailer === 'smtp' && $m->host) {
                config([
                    'mail.mailers.smtp.host' => $m->host,
                    'mail.mailers.smtp.port' => $m->port ?: 587,
                    'mail.mailers.smtp.username' => $m->username,
                    'mail.mailers.smtp.password' => $m->password,
                    'mail.mailers.smtp.encryption' => $m->encryption ?: null,
                ]);
            }
        } catch (\Throwable) {
            // ayarlar yoksa varsayılan (log) mailer kullanılır
        }
    }

    /** Doğrulama ve şifre sıfırlama e-postalarını Türkçeleştirir. */
    private function localizeAuthEmails(): void
    {
        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('E-posta Adresinizi Doğrulayın — Organik Ürün')
                ->greeting('Merhaba ' . ($notifiable->name ?? '') . ',')
                ->line('Organik Ürün hesabınızı oluşturduğunuz için teşekkürler. Hesabınızı etkinleştirmek için aşağıdaki butona tıklayın.')
                ->action('E-postamı Doğrula', $url)
                ->line('Bu işlemi siz yapmadıysanız bu e-postayı yok sayabilirsiniz.')
                ->salutation('Sağlıkla kalın,
Organik Ürün');
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = url(route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()], false));

            return (new MailMessage)
                ->subject('Şifre Sıfırlama — Organik Ürün')
                ->greeting('Merhaba,')
                ->line('Şifre sıfırlama talebinde bulundunuz. Yeni şifre belirlemek için aşağıdaki butona tıklayın.')
                ->action('Şifremi Sıfırla', $url)
                ->line('Bu bağlantı 60 dakika içinde geçerliliğini yitirir.')
                ->line('Talebi siz yapmadıysanız bu e-postayı yok sayabilirsiniz.')
                ->salutation('Organik Ürün');
        });
    }
}
