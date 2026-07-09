<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * İletişim formundan gelen mesaj → yönetici adresine (config mail.admin_notifications).
 * Reply-To gönderenin e-postasıdır; yanıt doğrudan müşteriye gider.
 */
class ContactMessageMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public ?string $senderPhone,
        public string $messageBody,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'İletişim Formu — ' . $this->senderName,
            replyTo: [new Address($this->senderEmail, $this->senderName)],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-message');
    }
}
