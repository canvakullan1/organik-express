<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Wishlist\WishlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AccountController extends Controller
{
    public function index(WishlistService $wishlist, LoyaltyService $loyalty)
    {
        return view('storefront.account.index', [
            'user' => auth()->user(),
            'wishlistCount' => $wishlist->count(),
            'loyaltyBalance' => $loyalty->balance(auth()->user()),
        ]);
    }

    public function loyalty(LoyaltyService $loyalty)
    {
        $user = auth()->user();

        return view('storefront.account.loyalty', [
            'balance' => $loyalty->balance($user),
            'transactions' => $user->loyaltyTransactions()->paginate(20),
        ]);
    }

    /** Bilgilerim — ad, e-posta, telefon, şifre. */
    public function profile()
    {
        return view('storefront.account.profile', [
            'user' => auth()->user(),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'current_password' => ['required_with:password', 'nullable', 'current_password'],
        ], [
            'current_password.current_password' => 'Mevcut şifreniz hatalı.',
            'password.confirmed' => 'Yeni şifre tekrarı eşleşmiyor.',
        ]);

        $emailChanged = $user->email !== $data['email'];

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'] ?? null;

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if ($emailChanged) {
            // Yeni e-postanın yeniden doğrulanması gerekir.
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();

            return redirect()->route('verification.notice')
                ->with('success', 'Bilgileriniz güncellendi. Yeni e-postanıza doğrulama bağlantısı gönderildi.');
        }

        return redirect()->route('account.profile')
            ->with('success', 'Bilgileriniz güncellendi.');
    }

    /** İletişim İzinleri — e-bülten, e-posta ve SMS bildirim tercihleri. */
    public function preferences()
    {
        $user = auth()->user();

        $newsletter = NewsletterSubscriber::where('email', $user->email)
            ->value('is_active');

        return view('storefront.account.preferences', [
            'user' => $user,
            'newsletter' => (bool) $newsletter,
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $user->accepts_marketing_email = $request->boolean('accepts_marketing_email');
        $user->accepts_sms = $request->boolean('accepts_sms');
        $user->save();

        // E-bülten aboneliği e-posta bazlı ayrı tabloda tutulur.
        $newsletter = $request->boolean('newsletter');
        NewsletterSubscriber::updateOrCreate(
            ['email' => $user->email],
            ['is_active' => $newsletter, 'source' => 'account'],
        );

        return redirect()->route('account.preferences')
            ->with('success', 'İletişim tercihleriniz kaydedildi.');
    }
}
