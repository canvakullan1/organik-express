<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function store(Request $request)
    {
        // Bal küpü (honeypot): bot bu gizli alanı doldurursa sessizce başarı dön.
        if (filled($request->input('website'))) {
            return response()->json(['ok' => true, 'message' => 'Teşekkürler!']);
        }

        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
        ], [], ['email' => 'e-posta']);

        $subscriber = NewsletterSubscriber::firstOrCreate(
            ['email' => mb_strtolower($data['email'])],
            ['source' => 'ana-sayfa', 'is_active' => true]
        );

        $message = $subscriber->wasRecentlyCreated
            ? 'Aramıza hoş geldiniz! Fırsatlardan ilk siz haberdar olacaksınız.'
            : 'Zaten kayıtlısınız — teşekkürler!';

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
