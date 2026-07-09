<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Mail\ContactMessageMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /** İletişim formu gönderimi → yönetici adresine e-posta. */
    public function send(Request $request)
    {
        // Bot tuzağı (honeypot): 'website' alanı doluysa sessizce başarı dön.
        if (filled($request->input('website'))) {
            return back()->with('contact_ok', true)->withFragment('iletisim-form');
        }

        $data = $request->validate([
            'name'    => ['required', 'string', 'max:120'],
            'email'   => ['required', 'email', 'max:180'],
            'phone'   => ['nullable', 'string', 'max:40'],
            'message' => ['required', 'string', 'max:5000'],
        ], [
            'name.required'    => 'Lütfen adınızı girin.',
            'email.required'   => 'Lütfen e-posta adresinizi girin.',
            'email.email'      => 'Geçerli bir e-posta adresi girin.',
            'message.required' => 'Lütfen mesajınızı yazın.',
        ]);

        if ($to = config('mail.admin_notifications')) {
            Mail::to($to)->send(new ContactMessageMail(
                $data['name'],
                $data['email'],
                $data['phone'] ?? null,
                $data['message'],
            ));
        }

        return back()->with('contact_ok', true)->withFragment('iletisim-form');
    }
}
