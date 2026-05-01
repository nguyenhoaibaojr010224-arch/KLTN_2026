<?php

namespace App\Mail;

use App\Models\KhachHang;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public KhachHang $khachHang,
        public string $code,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mã xác minh tài khoản PharmaGo',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification-code',
        );
    }
}
