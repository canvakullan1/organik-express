<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /** Doğrulama bilgilendirme ekranı. */
    public function notice(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->route('account.index')
            : view('auth.verify-email');
    }

    /** İmzalı bağlantıdan doğrulama. */
    public function verify(EmailVerificationRequest $request)
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->fulfill(); // email_verified_at set + Verified event
        }

        return redirect()->route('account.index')
            ->with('success', 'E-posta adresiniz doğrulandı. Hoş geldiniz!');
    }

    /** Doğrulama mailini yeniden gönder. */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('account.index');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Doğrulama bağlantısı e-postanıza yeniden gönderildi.');
    }
}
